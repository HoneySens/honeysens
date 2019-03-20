from __future__ import absolute_import

import docker
import json
import logging
import netifaces
import os
import shutil
import subprocess
import threading
import time
# import traceback

from .utils import constants


IPTABLES_PATH = '/sbin/iptables'
# Name of the iptables sensor chain managed by this process
IPTABLES_CHAIN_LABEL = 'SENSOR'
# Label of the docker user-defined network that we designate for services
SERVICE_NETWORK = 'services'

_catchall_target = None  # The service instance that netfilter currently redirects packets to
_config = None
_config_dir = None
_docker = None
_interface = None
_logger = None
_services = None  # service_name -> {'container': container ID || None, 'image': image}
_services_lock = threading.Lock()
_platform = None  # An instance of the current platform module


def init(config_dir, config, hook_mgr, platform, interface):
    global _logger
    _logger = logging.getLogger(__name__)
    _logger.info('Initializing service module')
    global _config_dir, _config, _platform, _interface
    _config_dir = config_dir
    _config = config
    _platform = platform
    _interface = interface
    hook_mgr.register_hook(constants.Hooks.ON_INIT, init_firewall)
    hook_mgr.register_hook(constants.Hooks.ON_APPLY_CONFIG, register_registry_cert)
    hook_mgr.register_hook(constants.Hooks.ON_APPLY_CONFIG, enable_docker)
    hook_mgr.register_hook(constants.Hooks.ON_APPLY_CONFIG, setup_networking)
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


def setup_networking(config, server_response, reset_network):
    service_network = config.get('general', 'service_network')
    # Check if there is an existing service network
    for n in _docker.networks.list():
        if n.name == SERVICE_NETWORK:
            if n.attrs['IPAM']['Config'][0]['Subnet'] != service_network:
                # In case that network config doesn't match our subnet definition, remove it
                _logger.warning('Existing service network uses an outdated subnet range, removing it')
                destroy_all()
                n.remove()
            else:
                # Register the name of an existing bridge interface with the platform module
                _platform.set_services_network_iface(n.attrs['Options']['com.docker.network.bridge.name'])
            break
    if SERVICE_NETWORK not in [n.name for n in _docker.networks.list()]:
        # Create the service network if it's not there yet
        bridge_name = _platform.generate_services_network_iface()
        ipam_pool = docker.types.IPAMPool(subnet=service_network, iprange=service_network)
        ipam_cfg = docker.types.IPAMConfig(pool_configs=[ipam_pool])
        _logger.info('Creating services network {} on bridge {}'.format(service_network, bridge_name))
        _docker.networks.create(SERVICE_NETWORK, ipam=ipam_cfg, options={'com.docker.network.bridge.name': bridge_name})
        _platform.set_services_network_iface(bridge_name)


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
    service_label = get_container_name(service)
    containers = _docker.containers.list(all=True)
    # Determine docker bridge IP
    collector_host = netifaces.ifaddresses(constants.DOCKER_BRIDGE)[2][0]['addr']
    # Remove known stale container if necessary
    if _services[service]['container'] is not None and get_full_image_name(_services[service]['container'].image) != _services[service]['image']:
        _logger.info('Removing stale container for service {}'.format(service))
        destroy(service)
        with _services_lock:
            _services[service]['container'] = None
    newly_registered = False
    if _services[service]['container'] is None:
        # Search for existing container with the appropriate name, otherwise create a new one
        container = None
        for c in containers:
            if service_label in c.name:
                # Destroy incompatible containers
                if get_full_image_name(c.image) != _services[service]['image']:
                    _logger.info('Removing incompatible container for service {}'.format(service))
                    destroy(service)
                else:
                    container = c
        if container is None:
            image_name = _services[service]['image']
            # Check image availability
            _logger.info('Refreshing image for service {} - {}'.format(service, image_name))
            image = _docker.images.pull(image_name)
            # Extract exposed ports
            ports = _services[service]['port_assignment']
            _logger.info('Creating new container for service {}'.format(service))
            # Launch containers with administrative network capabilities (so that they can set their own netfilter rules etc.)
            caps = ["NET_ADMIN"]
            # Create new container, either in raw or bridged mode
            if _services[service]['raw_network_access'] is True:
                container = _docker.containers.create(image_name, name=service_label, network_mode='host', cap_add=caps, environment={
                    'COLLECTOR_HOST': collector_host,
                    'COLLECTOR_PORT': constants.COLLECTOR_PORT,
                    'INTERFACE': _interface})
            else:
                container = _docker.containers.create(image_name, name=service_label, network=SERVICE_NETWORK, ports=ports, cap_add=caps, environment={
                    'COLLECTOR_HOST': collector_host,
                    'COLLECTOR_PORT': constants.COLLECTOR_PORT})
        with _services_lock:
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
        _logger.info('Starting container for service {}'.format(service))
        container.start()
    # Forward all incoming new and non-established traffic to catch-all containers via netfilter
    if _services[service]['catch_all'] is True and newly_registered:
        # Wait for the container to have an IP assigned
        container_ip = get_ip_from(service)
        while container_ip is None:
            _logger.warning('Waiting for container {} to have an IP address assigned'.format(service))
            time.sleep(1)
            container_ip = get_ip_from(service)
        _logger.info('Adding forwarding rules for catch-all container {} ({})'.format(service, container_ip))
        enable_catchall_for(service, container_ip)


def stop(service):
    if service not in _services:
        raise Exception('Unknown service {}'.format(service))
    container = _services[service]['container']
    if container in _docker.containers.list():
        _logger.info('Stopping container for service {}'.format(service))
        disable_catchall_for(service)
        try:
            container.stop()
        except Exception:
            # Containers who don't react properly to signals cause an exception here, but are usually still killed
            pass


def destroy(service):
    containers = _docker.containers.list(all=True)
    for c in containers:
        if get_container_name(service) in c.name:
            image_id = c.image.id
            disable_catchall_for(service)
            _logger.info('Removing container and image for service {}'.format(service))
            try:
                c.stop()
            except Exception:
                # Containers who don't react properly to signals cause an exception here, but are usually still killed.
                # Now wait a bit for the container to stop.
                time.sleep(5)
                pass
            c.remove()
            _docker.images.remove(image_id)
            with _services_lock:
                _services[service]['container'] = None


def stop_all():
    if _services is not None:
        with _services_lock:
            services = _services.keys()
        for s in services:
            stop(s)


def destroy_all():
    with _services_lock:
        services = _services.keys()
    for s in services:
        try:
            destroy(s)
        except Exception:
            _logger.error('Could not destroy service {}, internal error'.format(s))


def apply_services(config, server_response, reset_network):
    # Apply the service configuration we received
    if 'services' in server_response:
        arch = _platform.get_architecture()
        service_assignments = server_response['services']
        for service_id, service_archs in service_assignments.iteritems():
            if arch not in service_archs:
                _logger.warning('Service {} not available on this architecture ({})'.format(service_id, arch))
                continue
            service_image = '{}:{}/{}'.format(_config.get('server', 'name'), _config.get('server', 'port_https'), service_archs[arch]['uri'])
            # Add/update
            with _services_lock:
                if service_id in _services:
                    _services[service_id]['label'] = service_archs[arch]['label']
                    _services[service_id]['image'] = service_image
                    _services[service_id]['raw_network_access'] = service_archs[arch]['rawNetworkAccess']
                    _services[service_id]['catch_all'] = service_archs[arch]['catchAll']
                    _services[service_id]['port_assignment'] = json.loads(service_archs[arch]['portAssignment'])
                else:
                    _services[service_id] = {'label': service_archs[arch]['label'],
                                             'image': service_image,
                                             'raw_network_access': service_archs[arch]['rawNetworkAccess'],
                                             'catch_all': service_archs[arch]['catchAll'],
                                             'port_assignment': json.loads(service_archs[arch]['portAssignment']),
                                             'container': None}
            try:
                start(service_id)
            except Exception as e:
                _logger.error('Could not start service {} [{}] ({})'.format(service_id, service_archs[arch]['label'], str(e)))
                # traceback.print_exc()
        # Delete
        for candidate in (set(_services.keys()) - set(service_assignments.keys())):
            _logger.info('Removing service {} due to it not being scheduled'.format(candidate))
            try:
                destroy(candidate)
            except Exception as e:
                _logger.error('Couldn\'t cleanly remove service {} ({})'.format(candidate, str(e)))
                continue
            with _services_lock:
                _services.pop(candidate)


def register_registry_cert(config, server_response, reset_network):
    # Make registry certificate available for the docker client
    docker_config_dir = '/etc/docker/certs.d'
    server_cert_dir = '{}/{}:{}'.format(docker_config_dir, _config.get('server', 'name'), _config.get('server', 'port_https'))
    server_cert_path = '{}/ca.crt'.format(server_cert_dir)
    if not os.path.isfile(server_cert_path):
        if not os.path.isdir(server_cert_dir):
            _logger.info('Creating {}'.format(server_cert_dir))
            os.makedirs(server_cert_dir)
        shutil.copy('{}/{}'.format(_config_dir, _config.get('server', 'certfile')), server_cert_path)


def enable_docker(config, server_response, reset_network):
    global _docker, _services
    # dockerd has to be restarted to apply new proxy settings
    _platform.enable_docker(reset_network)
    _docker = docker.from_env()
    attempts = 0
    # Wait until dockerd is online
    while check_docker() is False:
        attempts += 1
        if attempts >= 10:
            _logger.error('Warning: Docker subsystem not usable')
            return
        time.sleep(1)
    # Prepare service list with containers already registered on this system
    if _services is None:
        with _services_lock:
            _services = {}
            for c in _docker.containers.list(all=True):
                _logger.info('Registering existing container {}'.format(c.name))
                _services[get_service_id(c.name)] = {'container': c}


# True, if the docker subsystem is initialized, online and reachable
def check_docker():
    if _docker is None:
        return False
    try:
        result = _docker.ping()
    except Exception as e:
        result = False
    return result


# Returns a dict with the status of each scheduled service
def get_status():
    result = {}
    with _services_lock:
        for service, service_data in _services.iteritems():
            if service_data['container'] is None:
                status = constants.ServiceStatus.SCHEDULED
            else:
                try:
                    service_data['container'].reload()
                    if service_data['container'].status == 'running':
                        status = constants.ServiceStatus.RUNNING
                    else:
                        status = constants.ServiceStatus.ERROR
                except Exception:
                    status = constants.ServiceStatus.ERROR
            result[service] = status
    return result


def get_full_image_name(image):
    return image.attrs['RepoTags'][0]


def get_ip_from(service):
    ip = None
    try:
        ip = _docker.containers.get(get_container_name(service)).attrs['NetworkSettings']['Networks'][SERVICE_NETWORK]['IPAddress']
    except Exception as e:
        pass
    return ip


def get_service_id(container_name):
    # Only return a service id using our internal naming scheme if the container name matches that scheme
    if container_name[0] == 's' and container_name[1:].isdigit():
        return container_name[1:]
    else:
        return container_name


def get_container_name(service_id):
    if service_id.isdigit():
        return 's{}'.format(service_id)
    else:
        return service_id


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
