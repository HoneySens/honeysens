import logging
import os
import shutil
import subprocess
import tarfile
import tempfile
import threading
import time

from manager import polling
from manager import services
from manager.platforms.generic import GenericPlatform
from manager.utils import communication
from manager.utils import constants

LED_MODE_OFF = 0
LED_MODE_STEADY_RED = 1
LED_MODE_STEADY_GREEN = 2
LED_MODE_STEADY_ORANGE = 3
LED_MODE_FLASH_RED = 4
LED_MODE_FLASH_GREEN = 5
LED_MODE_FLASH_ORANGE = 6

LED_GPIO_PIN_A = 60
LED_GPIO_PIN_B = 26
LED_GPIO_PIN_C = 44
LED_GPIO_HIGH = 'high'
LED_GPIO_LOW = 'low'
LED_CONTROLLER_INTERVAL = 1.0  # LED worker timing (in seconds)
LED_TRANSIENT_DURATION = 6 # Number of cycles (of length LED_CONTROLLER_INTERVAL) the transient mode should last

MAX_TIME_DIFF = 3  # Maximum tolerated time difference between sensor and server in seconds

DHCP_CONFIG_FILE = '/etc/dhcp/dhclient.conf'
EAPOL_CONFIG_DIR = '/etc/wpa_supplicant'
EAPOL_CONFIG_FILE = 'eapol.conf'

class Platform(GenericPlatform):

    config_dir = None
    config_archive = None
    interface = None
    led_controller = None
    logger = None
    proxy_cfg_dir = '/etc/systemd/system/docker.service.d'
    proxy_cfg_file = '{}/http-proxy.conf'.format(proxy_cfg_dir)

    def __init__(self, hook_mgr, interface, config_dir, config_archive):
        super(Platform, self).__init__(hook_mgr, interface, config_dir, config_archive)
        self.logger = logging.getLogger(__name__)
        self.logger.info('Initializing platform module: BeagleBone Black')
        hook_mgr.register_hook(constants.Hooks.ON_APPLY_CONFIG, self.apply_config)
        hook_mgr.register_hook(constants.Hooks.ON_APPLY_CONFIG, self.update)
        hook_mgr.register_hook(constants.Hooks.ON_BEFORE_POLL, self.update_system_time)
        hook_mgr.register_hook(constants.Hooks.ON_POLL, self.refresh_led_status)
        hook_mgr.register_hook(constants.Hooks.ON_CONN_ERROR, self.refresh_led_status)
        hook_mgr.register_hook(constants.Hooks.ON_SERVICE_DOWNLOAD_START, self.refresh_led_status)
        hook_mgr.register_hook(constants.Hooks.ON_SERVICE_DOWNLOAD_END, self.refresh_led_status)
        self.interface = interface
        self.config_dir = config_dir
        self.config_archive = config_archive
        # TODO Do this in a pythonesque way
        # (e.g. https://stackoverflow.com/questions/600268/mkdir-p-functionality-in-python/600612#600612)
        subprocess.call(['mkdir', '-p', self.proxy_cfg_dir])
        subprocess.call(['systemctl', 'daemon-reload'])
        self.start_systemd_unit('cntlm')
        self.led_controller = LEDController()
        self.led_controller.start()

    def get_architecture(self):
        return 'armhf'

    def get_type(self):
        return 'BeagleBone Black'

    # Enables the docker daemon (if it's not already running) or forces a restart of dockerd
    def enable_docker(self, force_restart):
        if force_restart:
            self.logger.info('Restarting docker service')
            self.restart_systemd_unit('docker')
        else:
            self.start_systemd_unit('docker')

    def apply_config(self, config, server_response, reset_network):
        if reset_network:
            # Disable all network interfaces
            subprocess.call(['ifdown', self.interface])
            eapol_config = '{}/{}'.format(EAPOL_CONFIG_DIR, EAPOL_CONFIG_FILE)
            # Update interface definition (/etc/network/interfaces)
            if config.get('eapol', 'mode') != '0':
                eapol_conf = eapol_config
            else:
                eapol_conf = None
            GenericPlatform.update_iface_configuration(self, self.interface, config.get('network', 'mode'),
                                                       address=config.get('network', 'address'),
                                                       netmask=config.get('network', 'netmask'),
                                                       gateway=config.get('network', 'gateway'),
                                                       dns=config.get('network', 'dns'),
                                                       eapol=eapol_conf)
            # EAPOL configuraion via wpa_supplicant
            GenericPlatform.configure_eapol(self, EAPOL_CONFIG_DIR, EAPOL_CONFIG_FILE, config.get('eapol', 'mode'),
                                            config.get('eapol', 'identity'), config.get('eapol', 'password'),
                                            config.get('eapol', 'ca_cert'), config.get('eapol', 'anon_identity'),
                                            config.get('eapol', 'client_cert'), config.get('eapol', 'client_key'),
                                            config.get('eapol', 'client_key_password'))
            # Configure DHCP client
            self.configure_dhcp_client(config.get('network', 'dhcp_hostname'))
            # Change MAC address if required
            if config.get('mac', 'mode') == '1':
                GenericPlatform.update_mac_address(self, self.interface, config.get('mac', 'address'))
            # Proxy configuration
            if config.get('proxy', 'mode') == '1':
                proxy = 'https://{}:{}'.format(config.get('proxy', 'host'), config.get('proxy', 'port'))
                self.logger.info('Registering proxy {}'.format(proxy))
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
            subprocess.call(['ifup', self.interface])

    def refresh_led_status(self):
        last_server_response = polling.get_last_server_response()
        if self.is_firmware_update_in_progress() or self.is_service_update_in_progress():
            self.led_controller.set_mode(LED_MODE_FLASH_GREEN)
        elif not polling.is_online():
            self.led_controller.set_mode(LED_MODE_FLASH_RED)
        else:
            if 'unhandledEvents' in last_server_response and last_server_response['unhandledEvents']:
                self.led_controller.set_mode(LED_MODE_STEADY_RED)
            else:
                self.led_controller.set_mode(LED_MODE_STEADY_GREEN)

    def start_systemd_unit(self, unit):
        subprocess.call(['systemctl', 'start', unit])

    def restart_systemd_unit(self, unit):
        subprocess.call(['systemctl', 'restart', unit])

    def stop_systemd_unit(self, unit):
        subprocess.call(['systemctl', 'stop', unit])

    def update_system_time(self, config, config_dir):
        r = communication.perform_https_request(config, config_dir, '#', communication.REQUEST_TYPE_HEAD, verify=False, sign=False)
        if 'date' not in r['headers']:
            return
        req_time = r['headers']['date']
        timestamp_from_server = time.mktime(time.strptime(req_time, '%a, %d %b %Y %H:%M:%S %Z')) - time.timezone
        timestamp_local = time.time()
        if abs(timestamp_from_server - timestamp_local) <= MAX_TIME_DIFF:
            # Tolerate a diff of a few seconds between sensor and server to prevent unnecessary clock updates
            return
        timestamp_target = time.strftime('%Y/%m/%d %H:%M:%S', time.localtime(timestamp_from_server))
        self.logger.info(f'Setting system time to {timestamp_target}')
        with open(os.devnull, 'w') as devnull:
            subprocess.call(['date', '-s', timestamp_target], stdout=devnull)

    def update(self, config, server_response, reset_network):
        # Don't update if an update is already running
        if self.is_firmware_update_in_progress():
            self.logger.warning('Firmware update already scheduled')
            return
        if 'firmware' in server_response and 'bbb' in server_response['firmware']:
            current_revision = GenericPlatform.get_current_revision(self)
            target_revision = server_response['firmware']['bbb']['revision']
            target_uri = server_response['firmware']['bbb']['uri']
            if current_revision != target_revision:
                tempdir = tempfile.mkdtemp()
                try:
                    self.set_firmware_update_in_progress(True)
                    self.refresh_led_status()
                    self.logger.info('Update: Current revision {} differs from target {}, attempting update'.format(current_revision, target_revision))
                    self.logger.info('Update: Removing all containers to free some space')
                    services.destroy_all()
                    self.logger.info('Update: Downloading new firmware from {}'.format(target_uri))
                    fw_tempfile = '{}/firmware.tar.gz'.format(tempdir)
                    with open(fw_tempfile, 'wb') as f:
                        communication.perform_https_request(config, self.config_dir, target_uri, communication.REQUEST_TYPE_GET, file_descriptor=f)
                    self.logger.info('Update: Firmware written to {}'.format(fw_tempfile))
                    self.logger.info('Update: Inspecting archive')
                    with tarfile.open(fw_tempfile) as fw_archive:
                        files = fw_archive.getnames()
                        if 'firmware.img' not in files or 'metadata.xml' not in files:
                            self.logger.error('Update: Invalid archive content')
                            raise Exception()
                    self.logger.info('Update: Writing image to microSD card')
                    ext_dev = '/dev/mmcblk0'
                    int_dev = '/dev/mmcblk1'
                    # Check for both internal and external block devices to avoid updating when no SD card is inserted
                    if not os.path.exists(ext_dev) or not os.path.exists(int_dev):
                        self.logger.error('Update: No microSD card found')
                        raise Exception()
                    ret = subprocess.call(['/bin/tar', '-xf', fw_tempfile, '--to-command=dd bs=512k of={}'.format(ext_dev), 'firmware.img'])
                    if ret != 0:
                        self.logger.error('Update: Can\'t write to microSD card')
                        raise Exception()
                    # Save current sensor configuration
                    self.logger.info('Update: Preserving sensor configuration, mounting {}p1'.format(ext_dev))
                    ret = subprocess.call(['/bin/mount', '{}p1'.format(ext_dev), '/mnt'])
                    if ret != 0:
                        self.logger.error('Update: Can\'t mount microSD partition')
                        raise Exception()
                    shutil.copy(self.config_archive, '/mnt')
                    ret = subprocess.call(['/bin/umount', '/mnt'])
                    if ret != 0:
                        self.logger.error('Update: Unmount of microSD partition failed')
                        raise Exception()
                    # Cleanup and reboot, the bootloader on the newly written microSD card
                    # should be executed prior to the interal one, thus triggering a reflashing of the system
                    self.logger.info('Update: Rebooting to trigger update')
                    shutil.rmtree(tempdir)
                    subprocess.call('/sbin/reboot')
                except Exception as e:
                    self.logger.error('Error during update process ({})'.format(str(e)))
                    shutil.rmtree(tempdir)
                    self.set_firmware_update_in_progress(False)

    def configure_dhcp_client(self, desired_hostname):
        """Creates a default DHCP config incorporating the given options."""
        target_config = ('option rfc3442-classless-static-routes code 121 = array of unsigned integer 8;\n'
                         'request subnet-mask, broadcast-address, time-offset, routers, domain-name, '
                         'domain-name-servers, domain-search, host-name, netbios-name-servers, netbios-scope, '
                         'interface-mtu, rfc3442-classless-static-routes, ntp-servers;\n')
        if desired_hostname is not None:
            target_config += f'send host-name = "{desired_hostname}";\n'
        if os.path.exists(DHCP_CONFIG_FILE):
            with open(DHCP_CONFIG_FILE, 'r') as f:
                active_config = f.read()
        else:
            active_config = ''
        if active_config != target_config:
            self.logger.info(f'Update: Writing new {DHCP_CONFIG_FILE}')
            with open(DHCP_CONFIG_FILE, 'w') as f:
                f.write(target_config)

    def notify_led(self, mode):
        # Sets a transient led mode as a form of notification
        self.led_controller.set_mode(mode, True)

    def cleanup(self):
        self.led_controller.stop()
        self.led_controller.join()


class LEDController(threading.Thread):

    ev_stop = threading.Event()
    logger = None
    mode = LED_MODE_OFF  # Active desired mode, can be temporarily overwritten by a transient mode
    mode_lock = threading.Lock()
    status = None  # The current LED state, either LED_MODE_OFF or LED_MODE_STEADY_*
    transient_mode = None  # Overwrites the default mode for a short amount of time
    transient_time_remaining = 0  # Remaining overwrite duration

    def __init__(self):
        threading.Thread.__init__(self)
        self.logger = logging.getLogger(__name__)
        self.logger.info('Initializing LED controller')
        # Enable required GPIO pins
        for pin in [LED_GPIO_PIN_A, LED_GPIO_PIN_B, LED_GPIO_PIN_C]:
            try:
                with open('/sys/class/gpio/export', 'w') as f:
                    f.write(str(pin))
                self.logger.debug('GPIO {} enabled'.format(pin))
            except IOError:
                self.logger.debug('GPIO {} is already enabled'.format(pin))

    def run(self):
        while not self.ev_stop.is_set():
            self.mode_lock.acquire()
            if self.transient_time_remaining > 0:
                self.process_mode(self.transient_mode)
                self.transient_time_remaining -= 1
            else:
                self.process_mode(self.mode)
            self.mode_lock.release()
            self.ev_stop.wait(LED_CONTROLLER_INTERVAL)
        self.logger.info('Stopping LED controller')

    def stop(self):
        self.ev_stop.set()

    def set_mode(self, mode, transient=False):
        self.mode_lock.acquire()
        if transient:
            self.logger.debug('Setting transient LED mode ({})'.format(mode))
            self.transient_mode = mode
            self.transient_time_remaining = LED_TRANSIENT_DURATION
        else:
            self.logger.debug('Setting steady LED mode ({})'.format(mode))
            self.mode = mode
        self.mode_lock.release()

    def process_mode(self, mode):
        # Applies the given mode to the current status
        status_target = self.mode2status(mode)
        if mode in [LED_MODE_FLASH_GREEN, LED_MODE_FLASH_RED, LED_MODE_FLASH_ORANGE]:
            # Dynamic transient modes: Toggle between off and the selected mode
            if self.status != status_target:
                self.set_status(status_target)
            else:
                self.set_status(LED_MODE_OFF)
        else:
            # Static transient modes: Just set the mode directly
            if self.status != status_target:
                self.set_status(status_target)

    def set_status(self, mode):
        # Changes the current LED status
        if mode not in [LED_MODE_OFF, LED_MODE_STEADY_RED, LED_MODE_STEADY_GREEN, LED_MODE_STEADY_ORANGE]:
            raise Exception('Invalid LED mode ({})'.format(mode))
        if mode == LED_MODE_OFF:
            self.logger.debug('LED: turning off')
            self.set_pin(LED_GPIO_PIN_A, LED_GPIO_HIGH)
            self.set_pin(LED_GPIO_PIN_B, LED_GPIO_HIGH)
            self.set_pin(LED_GPIO_PIN_C, LED_GPIO_HIGH)
            self.status = LED_MODE_OFF
        elif mode == LED_MODE_STEADY_RED:
            self.logger.debug('LED: red')
            self.set_pin(LED_GPIO_PIN_A, LED_GPIO_HIGH)
            self.set_pin(LED_GPIO_PIN_B, LED_GPIO_LOW)
            self.set_pin(LED_GPIO_PIN_C, LED_GPIO_HIGH)
            self.status = LED_MODE_STEADY_RED
        elif mode == LED_MODE_STEADY_GREEN:
            self.logger.debug('LED: green')
            self.set_pin(LED_GPIO_PIN_A, LED_GPIO_LOW)
            self.set_pin(LED_GPIO_PIN_B, LED_GPIO_HIGH)
            self.set_pin(LED_GPIO_PIN_C, LED_GPIO_HIGH)
            self.status = LED_MODE_STEADY_GREEN
        elif mode == LED_MODE_STEADY_ORANGE:
            self.logger.debug('LED: orange')
            self.set_pin(LED_GPIO_PIN_A, LED_GPIO_LOW)
            self.set_pin(LED_GPIO_PIN_B, LED_GPIO_LOW)
            self.set_pin(LED_GPIO_PIN_C, LED_GPIO_HIGH)
            self.status = LED_MODE_STEADY_ORANGE

    def set_pin(self, pin, value):
        if pin not in [LED_GPIO_PIN_A, LED_GPIO_PIN_B, LED_GPIO_PIN_C] or value not in [LED_GPIO_LOW, LED_GPIO_HIGH]:
            raise Exception('Invalid pin data ({}, {})'.format(pin, value))
        with open('/sys/class/gpio/gpio{}/direction'.format(pin), 'w') as f:
            f.write(value)

    def mode2status(self, mode):
        # Translates all LED modes into their respective steady modes that are compatible with set_status()
        if mode == LED_MODE_FLASH_RED:
            return LED_MODE_STEADY_RED
        elif mode == LED_MODE_FLASH_GREEN:
            return LED_MODE_STEADY_GREEN
        elif mode == LED_MODE_FLASH_ORANGE:
            return LED_MODE_STEADY_ORANGE
        else:
            return mode
