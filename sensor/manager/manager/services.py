from __future__ import absolute_import

import docker
import json
import netifaces
import os
import shutil
import subprocess
import time

from .utils import constants


IPTABLES_PATH = '/sbin/iptables'
# Name of the iptables sensor chain managed by this process
IPTABLES_CHAIN_LABEL = 'SENSOR'

_config_dir = None
_config = None
_docker = None
_services = None  # service_name -> {'container': container ID || None, 'image': image}
_platform = None  # An instance of the current platform module
_interface = None
_catchall_target = None  # The service instance that netfilter currently redirects packets to


def init(config_dir, config, hook_mgr, platform, interface):
    print('Initializing service module')
    global _config_dir, _config, _platform, _interface
    _config_dir = config_dir
    _config = config
    _platform = platform
    _interface = interface
    hook_mgr.register_hook(constants.Hooks.ON_INIT, init_firewall)
    hook_mgr.register_hook(constants.Hooks.ON_APPLY_CONFIG, register_registry_cert)
    hook_mgr.register_hook(constants.Hooks.ON_APPLY_CONFIG, enable_docker)
    hook_mgr.register_hook(constants.Hooks.ON_APPLY_CONFIG, adjust_firewall)
    hook_mgr.register_hook(constants.Hooks.ON_APPLY_CONFIG, apply_services)


def init_firewall():
    # Create sensor chains for both default and nat tables
    with open(os.devnull, 'w') as devnull:
        subprocess.call([IPTABLES_PATH, '-N', IPTABLES_CHAIN_LABEL], stderr=devnull)
        subprocess.call([IPTABLES_PATH, '-t', 'nat', '-N', IPTABLES_CHAIN_LABEL], stderr=devnull)
    # Flush sensor chains
    subprocess.call([IPTABLES_PATH, '-F', IPTABLES_CHAIN_LABEL])
    subprocess.call([IPTABLES_PATH, '-t', 'nat', '-F', IPTABLES_CHAIN_LABEL])


def adjust_firewall(config, server_response, reset_network):
    # Redirect incoming packets to sensor chains
    with open(os.devnull, 'w') as devnull:
        if reset_network:
            # On network changes, ensure that each of these rules is at the top or bottom of their respective chains
            # by removing the existing rules
            if subprocess.call([IPTABLES_PATH, '-C', 'FORWARD', '-i', _interface, '-j', IPTABLES_CHAIN_LABEL], stderr=devnull) == 0:
                subprocess.call([IPTABLES_PATH, '-D', 'FORWARD', '-i', _interface, '-j', IPTABLES_CHAIN_LABEL])
            if subprocess.call([IPTABLES_PATH, '-t', 'nat', '-C', 'PREROUTING', '-i', _interface, '-j', IPTABLES_CHAIN_LABEL], stderr=devnull) == 0:
                subprocess.call([IPTABLES_PATH, '-t', 'nat', '-D', 'PREROUTING', '-i', _interface, '-j', IPTABLES_CHAIN_LABEL])
        if subprocess.call([IPTABLES_PATH, '-C', 'FORWARD', '-i', _interface, '-j', IPTABLES_CHAIN_LABEL], stderr=devnull) != 0:
            subprocess.call([IPTABLES_PATH, '-I', 'FORWARD', '-i', _interface, '-j', IPTABLES_CHAIN_LABEL])
        if subprocess.call([IPTABLES_PATH, '-t', 'nat', '-C', 'PREROUTING', '-i', _interface, '-j', IPTABLES_CHAIN_LABEL], stderr=devnull) != 0:
            subprocess.call([IPTABLES_PATH, '-t', 'nat', '-A', 'PREROUTING', '-i', _interface, '-j', IPTABLES_CHAIN_LABEL])


def start(service):
    if service not in _services:
        raise Exception('Unknown service {}'.format(service))
    containers = _docker.containers.list(all=True)
    # Determine docker bridge IP
    collector_host = netifaces.ifaddresses(constants.DOCKER_BRIDGE)[2][0]['addr']
    # Remove known stale container if necessary
    if _services[service]['container'] is not None and get_full_image_name(_services[service]['container'].image) != _services[service]['image']:
        print('SERVICES: Removing stale container for service {}'.format(service))
        destroy(service)
        _services[service]['container'] = None
    newly_registered = False
    if _services[service]['container'] is None:
        # Search for existing container with the appropriate name, otherwise create a new one
        container = None
        for c in containers:
            if service in c.name:
                # Destroy incompatible containers
                if get_full_image_name(c.image) != _services[service]['image']:
                    print('SERVICES: Removing incompatible container for service {}'.format(service))
                    destroy(service)
                else:
                    container = c
        if container is None:
            image_name = _services[service]['image']
            # Check image availability
            print('SERVICES: Refreshing image for service {} - {}'.format(service, image_name))
            image = _docker.images.pull(image_name)
            # Extract exposed ports
            ports = _services[service]['port_assignment']
            print('SERVICES: Creating new container for service {}'.format(service))
            # Launch containers with administrative network capabilities (so that they can set their own netfilter rules etc.)
            caps = ["NET_ADMIN"]
            # Create new container, either in raw or bridged mode
            if _services[service]['raw_network_access'] is True:
                container = _docker.containers.create(image_name, name=service, network_mode='host', cap_add=caps, environment={
                    'COLLECTOR_HOST': collector_host,
                    'COLLECTOR_PORT': constants.COLLECTOR_PORT,
                    'INTERFACE': _interface})
            else:
                container = _docker.containers.create(image_name, name=service, ports=ports, cap_add=caps, environment={
                    'COLLECTOR_HOST': collector_host,
                    'COLLECTOR_PORT': constants.COLLECTOR_PORT})
        _services[service]['container'] = container
        newly_registered = True
    # Ensure that service container really exists
    containers = _docker.containers.list(all=True)
    container = _services[service]['container']
    #if cid not in [c['Id'] for c in containers]:
        # TODO handle that somehow
        #raise Exception('Stale container ID {}'.format(cid))
    # Ensure that service container isn't running already
    if container not in _docker.containers.list():
        print('SERVICES: Starting container for service {}'.format(service))
        container.start()
    # Forward all incoming new and non-established traffic to catch-all containers via netfilter
    if _services[service]['catch_all'] is True and newly_registered:
        # Wait for the container to have an IP assigned
        container_ip = get_ip_from(service)
        while container_ip is None:
            print('SERVICES: Waiting for container {} to have an IP address assigned'.format(service))
            time.sleep(1)
            container_ip = get_ip_from(service)
        print('SERVICES: Adding forwarding rules for catch-all container {} ({})'.format(service, container_ip))
        enable_catchall_for(service, container_ip)


def stop(service):
    if service not in _services:
        raise Exception('Unknown service {}'.format(service))
    container = _services[service]['container']
    if container in _docker.containers.list():
        print('SERVICES: Stopping container for service {}'.format(service))
        disable_catchall_for(service)
        try:
            container.stop()
        except Exception:
            # Containers who don't react properly to signals cause an exception here, but are usually still killed
            pass


def destroy(service):
    containers = _docker.containers.list(all=True)
    for c in containers:
        if service in c.name:
            image_id = c.image.id
            disable_catchall_for(service)
            print('SERVICES: Removing container and image for service {}'.format(service))
            try:
                c.stop()
            except Exception:
                # Containers who don't react properly to signals cause an exception here, but are usually still killed.
                # Now wait a bit for the container to stop.
                time.sleep(5)
                pass
            c.remove()
            _docker.images.remove(image_id)


def stop_all():
    if _services is not None:
        for s in _services:
            stop(s)


def destroy_all():
    for s in _services:
        try:
            destroy(s)
        except Exception:
            print('SERVICES: Could not destroy service {}, internal error'.format(s))


def apply_services(config, server_response, reset_network):
    global _services
    # Prepare service list with containers already registered on this system
    if _services is None:
        _services = {}
        for c in _docker.containers.list(all=True):
            print('SERVICES: Registering existing container {}'.format(c.name))
            _services[c.name] = {'container': None}
    # Apply the service configuration we received
    if 'services' in server_response:
        arch = _platform.get_architecture()
        service_assignments = server_response['services']
        for service_name, service_archs in service_assignments.iteritems():
            if arch not in service_archs:
                print('SERVICES: Service {} not available on this architecture ({})'.format(service_name, arch))
                continue
            service_image = '{}:{}/{}'.format(_config.get('server', 'name'), _config.get('server', 'port_https'), service_archs[arch]['uri'])
            # Add/update
            if service_name in _services:
                _services[service_name]['image'] = service_image
                _services[service_name]['raw_network_access'] = service_archs[arch]['rawNetworkAccess']
                _services[service_name]['catch_all'] = service_archs[arch]['catchAll']
                _services[service_name]['port_assignment'] = json.loads(service_archs[arch]['portAssignment'])
            else:
                _services[service_name] = {'image': service_image,
                                           'raw_network_access': service_archs[arch]['rawNetworkAccess'],
                                           'catch_all': service_archs[arch]['catchAll'],
                                           'port_assignment': json.loads(service_archs[arch]['portAssignment']),
                                           'container': None}
            try:
                start(service_name)
            except Exception:
                print('SERVICES: Could not start service {}, internal error'.format(service_name))
        # Delete
        for candidate in (set(_services.keys()) - set(service_assignments.keys())):
            print('SERVICES: Removing service {} due to it being no longer active'.format(candidate))
            try:
                destroy(candidate)
            except Exception:
                print('SERVICES: Couldn\'t remove service {}'.format(candidate))
                continue
            _services.pop(candidate)


def register_registry_cert(config, server_response, reset_network):
    # Make registry certificate available for the docker client
    docker_config_dir = '/etc/docker/certs.d'
    server_cert_dir = '{}/{}:{}'.format(docker_config_dir, _config.get('server', 'name'), _config.get('server', 'port_https'))
    server_cert_path = '{}/ca.crt'.format(server_cert_dir)
    if not os.path.isfile(server_cert_path):
        if not os.path.isdir(server_cert_dir):
            print('SERVICES: Creating {}'.format(server_cert_dir))
            os.makedirs(server_cert_dir)
        shutil.copy('{}/{}'.format(_config_dir, _config.get('server', 'certfile')), server_cert_path)


def enable_docker(config, server_response, reset_network):
    global _docker
    # dockerd has to be restarted to apply new proxy settings
    _platform.enable_docker(reset_network)
    _docker = docker.from_env()
    attempts = 0
    # Wait until dockerd is online
    while check_docker() is False:
        attempts += 1
        if attempts >= 10:
            print('SERVICES: Warning: Docker subsystem not usable')
            return
        time.sleep(1)


# True, if the docker subsystem is initialized, online and reachable
def check_docker():
    if _docker is None:
        return False
    try:
        result = _docker.ping()
    except Exception as e:
        result = False
    return result


def get_full_image_name(image):
    return image.attrs['RepoTags'][0]


def get_ip_from(service):
    ip = None
    try:
        ip = _docker.containers.get(service).attrs['NetworkSettings']['Networks']['bridge']['IPAddress']
    except Exception as e:
        pass
    return ip


def enable_catchall_for(service, ip):
    global _catchall_target
    with open(os.devnull, 'w') as devnull:
        if subprocess.call([IPTABLES_PATH, '-t', 'nat', '-C', IPTABLES_CHAIN_LABEL, '-j', 'DNAT', '--to-destination', ip], stderr=devnull) != 0:
            subprocess.call([IPTABLES_PATH, '-t', 'nat', '-A', IPTABLES_CHAIN_LABEL, '-j', 'DNAT', '--to-destination', ip])
        if subprocess.call([IPTABLES_PATH, '-C', IPTABLES_CHAIN_LABEL, '-d', ip, '-j', 'ACCEPT'], stderr=devnull) != 0:
            subprocess.call([IPTABLES_PATH, '-I', IPTABLES_CHAIN_LABEL, '-d', ip, '-j', 'ACCEPT'])
    _catchall_target = service


def disable_catchall_for(service):
    global _catchall_target
    if _catchall_target == service:
        ip = get_ip_from(service)
        with open(os.devnull, 'w') as devnull:
            if subprocess.call([IPTABLES_PATH, '-t', 'nat', '-C', IPTABLES_CHAIN_LABEL, '-j', 'DNAT', '--to-destination', ip], stderr=devnull) == 0:
                subprocess.call([IPTABLES_PATH, '-t', 'nat', '-D', IPTABLES_CHAIN_LABEL, '-j', 'DNAT', '--to-destination', ip])
            if subprocess.call([IPTABLES_PATH, '-C', IPTABLES_CHAIN_LABEL, '-d', ip, '-j', 'ACCEPT'], stderr=devnull) == 0:
                subprocess.call([IPTABLES_PATH, '-D', IPTABLES_CHAIN_LABEL, '-d', ip, '-j', 'ACCEPT'])
        _catchall_target = None


def cleanup():
    _docker.close()
    # Flush and remove sensor netfilter chains
    with open(os.devnull, 'w') as devnull:
        if subprocess.call([IPTABLES_PATH, '-C', 'FORWARD', '-i', _interface, '-j', IPTABLES_CHAIN_LABEL], stderr=devnull) == 0:
            subprocess.call([IPTABLES_PATH, '-D', 'FORWARD', '-i', _interface, '-j', IPTABLES_CHAIN_LABEL])
        if subprocess.call([IPTABLES_PATH, '-t', 'nat', '-C', 'PREROUTING', '-i', _interface, '-j', IPTABLES_CHAIN_LABEL], stderr=devnull) == 0:
            subprocess.call([IPTABLES_PATH, '-t', 'nat', '-D', 'PREROUTING', '-i', _interface, '-j', IPTABLES_CHAIN_LABEL])
    subprocess.call([IPTABLES_PATH, '-F', IPTABLES_CHAIN_LABEL])
    subprocess.call([IPTABLES_PATH, '-t', 'nat', '-F', IPTABLES_CHAIN_LABEL])
    subprocess.call([IPTABLES_PATH, '-X', IPTABLES_CHAIN_LABEL])
    subprocess.call([IPTABLES_PATH, '-t', 'nat', '-X', IPTABLES_CHAIN_LABEL])
