from __future__ import absolute_import

import os
import shutil
import subprocess
import tarfile
import tempfile
import time

from manager import services
from manager.platforms.generic import GenericPlatform
from manager.utils import communication
from manager.utils import constants


class Platform(GenericPlatform):

    interface = None
    config_dir = None
    config_archive = None
    proxy_cfg_dir = '/etc/systemd/system/docker.service.d'
    proxy_cfg_file = '{}/http-proxy.conf'.format(proxy_cfg_dir)

    def __init__(self, hook_mgr, interface, config_dir, config_archive):
        print('Initializing platform module: BeagleBone Black')
        hook_mgr.register_hook(constants.Hooks.ON_APPLY_CONFIG, self.apply_config)
        hook_mgr.register_hook(constants.Hooks.ON_APPLY_CONFIG, self.update)
        hook_mgr.register_hook(constants.Hooks.ON_BEFORE_POLL, self.update_system_time)
        self.interface = interface
        self.config_dir = config_dir
        self.config_archive = config_archive
        # TODO Do this in a pythonesque way
        # (e.g. https://stackoverflow.com/questions/600268/mkdir-p-functionality-in-python/600612#600612)
        subprocess.call(['mkdir', '-p', self.proxy_cfg_dir])
        subprocess.call(['systemctl', 'daemon-reload'])
        self.start_systemd_unit('cntlm')

    def get_architecture(self):
        return 'armhf'

    # Enables the docker daemon (if it's not already running) or forces a restart of dockerd
    def enable_docker(self, force_restart):
        if force_restart:
            print('PLATFORM: Restarting docker service')
            self.restart_systemd_unit('docker')
        else:
            self.start_systemd_unit('docker')

    def apply_config(self, config, server_response, reset_network):
        if reset_network:
            # Disable all network interfaces
            self.stop_systemd_unit('networking')
            # Update interface definition (/etc/network/interfaces)
            GenericPlatform.update_iface_configuration(self, self.interface, config.get('network', 'mode'),
                                                       address=config.get('network', 'address'),
                                                       netmask=config.get('network', 'netmask'),
                                                       gateway=config.get('network', 'gateway'),
                                                       dns=config.get('network', 'dns'))
            # Change MAC address if required
            if config.get('mac', 'mode') == '1':
                GenericPlatform.update_mac_address(self, self.interface, config.get('mac', 'address'))
            if config.get('proxy', 'mode') == '1':
                credentials = ''
                if config.get('proxy', 'user') != '':
                    credentials = '{}:{}@'.format(config.get('proxy', 'user'), config.get('proxy', 'password'))
                proxy = 'https://{}{}:{}/'.format(credentials, config.get('proxy', 'host'), config.get('proxy', 'port'))
                print('PLATFORM: Registering proxy {}'.format(proxy))
                # Reconfigure cntlm
                GenericPlatform.configure_cntlm(self, '{}:{}'.format(config.get('proxy', 'host'), config.get('proxy', 'port')),
                                                config.get('proxy', 'user'), config.get('proxy', 'password'))
                self.restart_systemd_unit('cntlm')
                # Docker daemon proxy handling
                # See: https://docs.docker.com/config/daemon/systemd/
                proxy_cfg = '[Service]\nEnvironment="HTTP_PROXY=http://127.0.0.1:3128/" "HTTPS_PROXY=http://127.0.0.1:3128/"'
                # Only write changes if they differ
                if os.path.isfile(self.proxy_cfg_file):
                    with open(self.proxy_cfg_file, 'r') as f:
                        current_cfg = f.read().strip()
                else:
                    current_cfg = None
                if current_cfg != proxy_cfg:
                    with open(self.proxy_cfg_file, 'w') as f:
                        f.write(proxy_cfg)
                    subprocess.call(['systemctl', 'daemon-reload'])
                    self.restart_systemd_unit('docker')
            else:
                if os.path.isfile(self.proxy_cfg_file):
                    os.remove(self.proxy_cfg_file)
                    subprocess.call(['systemctl', 'daemon-reload'])
                    self.restart_systemd_unit('docker')
            # Restart network interfaces
            self.start_systemd_unit('networking')

    def start_systemd_unit(self, unit):
        subprocess.call(['systemctl', 'start', unit])

    def restart_systemd_unit(self, unit):
        subprocess.call(['systemctl', 'restart', unit])

    def stop_systemd_unit(self, unit):
        subprocess.call(['systemctl', 'stop', unit])

    def update_system_time(self, config, config_dir):
        r = communication.perform_https_request(config, config_dir, '#', communication.REQUEST_TYPE_HEAD, verify=False)
        if 'date' not in r['headers']:
            return
        req_time = r['headers']['date']
        t = time.localtime(time.mktime(time.strptime(req_time, '%a, %d %b %Y %H:%M:%S %Z')) - time.timezone)
        subprocess.call(['date', '-s', time.strftime('%Y/%m/%d %H:%M:%S', t)])

    def update(self, config, server_response, reset_network):
        if 'firmware' not in server_response or 'bbb' not in server_response['firmware']:
            return
        current_revision = GenericPlatform.get_current_revision(self)
        target_revision = server_response['firmware']['bbb']['revision']
        target_uri = server_response['firmware']['bbb']['uri']
        if current_revision != target_revision:
            # TODO Signal update process during next polls
            tempdir = tempfile.mkdtemp()
            try:
                print('PLATFORM: Current revision {} differs from target {}, attempting update'.format(current_revision, target_revision))
                print('PLATFORM: Removing all containers to free some space')
                services.destroy_all()
                print('PLATFORM: Downloading new firmware from {}'.format(target_uri))
                fw_tempfile = '{}/firmware.tar.gz'.format(tempdir)
                with open(fw_tempfile, 'w') as f:
                    communication.perform_https_request(config, self.config_dir, target_uri, communication.REQUEST_TYPE_GET, file_descriptor=f)
                print('PLATFORM: Firmware written to {}'.format(fw_tempfile))
                print('PLATFORM: Inspecting archive')
                with tarfile.open(fw_tempfile) as fw_archive:
                    files = fw_archive.getnames()
                    if 'firmware.img' not in files or 'metadata.xml' not in files:
                        print('PLATFORM: Error: Invalid archive contents')
                        raise Exception()
                print('PLATFORM: Writing image to microSD card')
                ext_dev = '/dev/mmcblk0'
                int_dev = '/dev/mmcblk1'
                # Check for both internal and external block devices to avoid updating when no SD card is inserted
                if not os.path.exists(ext_dev) or not os.path.exists(int_dev):
                    print('PLATFORM: Error: No microSD card found')
                    raise Exception()
                ret = subprocess.call(['/bin/tar', '-xf', fw_tempfile, '--to-command=dd bs=512k of={}'.format(ext_dev), 'firmware.img'])
                if ret != 0:
                    print('PLATFORM: Error: Can\'t write to microSD card')
                    raise Exception()
                # Save current sensor configuration
                print('PLATFORM: Preserving sensor configuration, mounting {}p1'.format(ext_dev))
                ret = subprocess.call(['/bin/mount', '{}p1'.format(ext_dev), '/mnt'])
                if ret != 0:
                    print('PLATFORM: Error: Can\'t mount microSD partition')
                    raise Exception()
                shutil.copy(self.config_archive, '/mnt')
                ret = subprocess.call(['/bin/umount', '/mnt'])
                if ret != 0:
                    print('PLATFORM: Error: Unmount of microSD partition failed')
                    raise Exception()
                # Cleanup and reboot, the bootloader on the newly written microSD card
                # should be executed prior to the interal one, thus triggering a reflashing of the system
                print('PLATFORM: Rebooting to trigger update')
                shutil.rmtree(tempdir)
                subprocess.call('/sbin/reboot')
            except Exception as e:
                print('PLATFORM: Error during update process ({})'.format(e.message))
                shutil.rmtree(tempdir)
