from __future__ import absolute_import

import os
import re
import shutil
import subprocess
import tarfile
import tempfile
import time
import yaml

from manager.platforms.generic import GenericPlatform
from manager.utils import communication
from manager.utils import constants

# We expect this to be the host's docker socket that is mounted into the sensor container
HOST_DOCKER_SOCKET = 'unix:///var/run/docker.host.sock'
# Project directory that contains the files docker-compose.yml and .env that belong to this sensor deployment,
# usually mounted as volume from the host fs
COMPOSEFILE_DIR = '/mnt'
# Path to store newly downloaded firmware
UPDATE_FW_DESTINATION = '/tmp/firmware'


class Platform(GenericPlatform):

    interface = None
    config_dir = None
    config_archive = None

    def __init__(self, hook_mgr, interface, config_dir, config_archive):
        print('Initializing platform module: Docker')
        hook_mgr.register_hook(constants.Hooks.ON_INIT, self.cleanup_prev_sensor)
        hook_mgr.register_hook(constants.Hooks.ON_INIT, self.init_networking)
        hook_mgr.register_hook(constants.Hooks.ON_APPLY_CONFIG, self.update_resolv_conf)
        hook_mgr.register_hook(constants.Hooks.ON_APPLY_CONFIG, self.apply_config)
        hook_mgr.register_hook(constants.Hooks.ON_APPLY_CONFIG, self.update)
        self.interface = interface
        self.config_dir = config_dir
        self.config_archive = config_archive

    def get_architecture(self):
        return 'amd64'

    # Enables the docker daemon (if it's not already running) or forces a restart of dockerd
    def enable_docker(self, force_restart):
        if force_restart:
            print('PLATFORM: Restarting docker service')
            subprocess.call(['s6-svc', '-wr', '-t', '-u', '/var/run/s6/services/docker/'])
        else:
            subprocess.call(['s6-svc', '-wu', '-u', '/var/run/s6/services/docker/'])

    # Only used during unattended updates: Removes artifacts of the previous (now unused) sensor container
    def cleanup_prev_sensor(self):
        if 'PREV_PREFIX' in os.environ:
            print('PLATFORM: Cleaning up sensor artifacts with prefix {}'.format(os.environ['PREV_PREFIX']))
            subprocess.call(['/usr/bin/docker-compose',
                             '-H', HOST_DOCKER_SOCKET,
                             '-p', os.environ['PREV_PREFIX'],
                             'down', '-v'],
                            cwd=COMPOSEFILE_DIR,
                            env=os.environ.copy())

    # Configures the network stack depending on the chosen network mode
    def init_networking(self):
        # Figure out our own container ID
        my_id = None
        with open('/proc/self/cgroup', 'rb') as f:
            raw_cgroup = f.read()
        for cgroup in raw_cgroup.split('\n'):
            if 'docker' in cgroup:
                my_id = cgroup.split('/')[-1]
                break
        print('Local container ID: {}'.format(my_id))
        # Figure out which network mode this container launched with
        p = subprocess.Popen(['/usr/bin/docker',
                              '-H', HOST_DOCKER_SOCKET,
                              'inspect', my_id,
                              '-f', '{{.HostConfig.NetworkMode}}'],
                             stdout=subprocess.PIPE)
        out, err = p.communicate()
        network_mode = out.strip()
        print('Network mode: {}'.format(network_mode))


    def update_resolv_conf(self, config, server_response, reset_network):
        # DNS is provided via go-dnsmasq, but dockerd does its own DNS resolution by interpreting 'domain'
        # and 'search' statements in /etc/resolv.conf. Since the hostname 'honeysens' should be interpreted
        # without consideration of 'search' and 'domain' statements, we remove those and force users to always
        # specify the server name as FQDN.
        with open('/etc/resolv.conf', 'rb') as f:
            resolv_content = f.read()
        if 'search' in resolv_content or 'domain' in resolv_content:
            print('PLATFORM: Removing search and domain entries from /etc/resolv.conf')
            # Replace 'search' and 'domain' statements with comments
            resolv_content = re.sub('search\s\S+', '#', re.sub('domain\s\S+', '#', resolv_content))
            with open('/etc/resolv.conf', 'wb') as f:
                f.write(resolv_content)
            reset_network = True
        if reset_network:
            # Force a restart of go-dnsmasq to apply /etc/hosts changes
            print('PLATFORM: Restarting go-dnsmasq')
            subprocess.call(['killall', 'go-dnsmasq'])

    def apply_config(self, config, server_response, reset_network):
        if reset_network:
            # Update interface definition (/etc/network/interfaces)
            GenericPlatform.update_iface_configuration(self, self.interface, config.get('network', 'mode'),
                                                       address=config.get('network', 'address'),
                                                       netmask=config.get('network', 'netmask'),
                                                       gateway=config.get('network', 'gateway'),
                                                       dns=config.get('network', 'dns'))
            # Change MAC address if required
            if config.get('mac', 'mode') == '1':
                GenericPlatform.update_mac_address(self, self.interface, config.get('mac', 'address'))

            # Reconfigure cntlm
            if config.get('proxy', 'mode') == '1':
                GenericPlatform.configure_cntlm(self, '{}:{}'.format(config.get('proxy', 'host'),
                                                                     config.get('proxy', 'port')),
                                                config.get('proxy', 'user'), config.get('proxy', 'password'))
                subprocess.call(['s6-svc', '-wr', '-t', '-u', '/var/run/s6/services/cntlm/'])

            # Restart network interfaces
            if config.get('network', 'mode') != '2':
                subprocess.call(['ifdown', self.interface])
                subprocess.call(['ifup', self.interface])

    def update(self, config, server_response, reset_network):
        # Don't update if an update is already scheduled
        if os.path.isfile(UPDATE_FW_DESTINATION):
            print('PLATFORM: Firmware update already scheduled')
            return
        if 'firmware' in server_response and 'docker_x86' in server_response['firmware']:
            current_revision = GenericPlatform.get_current_revision(self)
            target_revision = server_response['firmware']['docker_x86']['revision']
            target_uri = server_response['firmware']['docker_x86']['uri']
            if current_revision != target_revision:
                tempdir = tempfile.mkdtemp()
                try:
                    print('PLATFORM: Current revision {} differs from target {}, attempting update'.format(current_revision, target_revision))
                    print('PLATFORM: Downloading new firmware from {}'.format(target_uri))
                    fw_tempfile = '{}/firmware.tar.gz'.format(tempdir)
                    with open(fw_tempfile, 'w') as f:
                        communication.perform_https_request(config, self.config_dir, target_uri, communication.REQUEST_TYPE_GET, file_descriptor=f)
                    print('PLATFORM: Firmware written to {}'.format(fw_tempfile))
                    print('PLATFORM: Extracting firmware')
                    with tarfile.open(fw_tempfile) as fw_archive:
                        fw_archive.extractall(tempdir)
                    if not os.path.isfile('{}/firmware.img'.format(tempdir)):
                        raise Exception()
                    print('PLATFORM: Moving firmware to {}'.format(UPDATE_FW_DESTINATION))
                    shutil.move('{}/firmware.img'.format(tempdir), UPDATE_FW_DESTINATION)
                    os.chmod(UPDATE_FW_DESTINATION, 0666)
                    print('PLATFORM: Loading new firmware')
                    subprocess.call(['/usr/bin/docker', '-H', HOST_DOCKER_SOCKET, 'load', '-i', UPDATE_FW_DESTINATION])
                    next_project = 'hs_{}_{}'.format(config.get('general', 'sensor_id'), int(time.time()))
                    print('PLATFORM: Launching new sensor container within compose prefix {}'.format(next_project))
                    # Update compose file with the new image version
                    with open('{}/docker-compose.yml'.format(COMPOSEFILE_DIR)) as f:
                        compose_content = yaml.load(f)
                    compose_content['services']['sensor']['image'] = 'honeysens/sensorx86:{}'.format(target_revision)
                    with open('{}/docker-compose.yml'.format(COMPOSEFILE_DIR), 'w') as f:
                        yaml.dump(compose_content, f, default_flow_style=False)
                    # Create a unique compose project name for the new sensor deployment
                    with open('{}/.env'.format(COMPOSEFILE_DIR), 'r') as f:
                        current_project = f.read().strip().split('=')[1]
                    with open('{}/.env'.format(COMPOSEFILE_DIR), 'w') as f:
                        f.write('COMPOSE_PROJECT_NAME={}'.format(next_project))
                    # Set PREV_PREFIX to the current compose prefix so that the new container can properly clean up
                    new_env = os.environ.copy()
                    new_env['PREV_PREFIX'] = current_project
                    subprocess.call(['/usr/bin/docker-compose',
                                     '-H', HOST_DOCKER_SOCKET,
                                     'up', '-d'],
                                    cwd=COMPOSEFILE_DIR,
                                    env=new_env)
                    # Shut the old sensor (ourselves) down
                    subprocess.call(['/usr/bin/docker-compose',
                                     '-H', HOST_DOCKER_SOCKET,
                                     '-p', current_project,
                                     'down'],
                                    cwd=COMPOSEFILE_DIR,
                                    env=os.environ.copy())
                except Exception as e:
                    print('PLATFORM: Error during update process ({})'.format(e.message))
                    shutil.rmtree(tempdir)
                    if os.path.isfile(UPDATE_FW_DESTINATION):
                        os.remove(UPDATE_FW_DESTINATION)
