import datetime
import importlib.metadata
import logging
import pprint
import zmq

from . import polling
from . import services
from .platforms import bbb
from .utils import constants

STATUS_OK = 0
STATUS_ERROR = 1


class CommandProcessor():

    config = None
    ev_stop = False
    logger = None
    manager = None
    zmq_context = None

    def __init__(self, zmq_context, manager):
        self.manager = manager
        self.zmq_context = zmq_context
        self.logger = logging.getLogger(__name__)
        self.logger.info('Initializing command processor')

    def start(self):
        socket = self.zmq_context.socket(zmq.REP)
        poller = zmq.Poller()

        try:
            self.logger.info('Listening on {}'.format(constants.CMD_SOCKET))
            socket.bind(constants.CMD_SOCKET)
            poller.register(socket, zmq.POLLIN)
        except ValueError as e:
            self.logger.error('Command processor couldn\'t be started ({})'.format(str(e)))
            return

        while not self.ev_stop:
            socks = dict(poller.poll(1000))
            if socks.get(socket) == zmq.POLLIN:
                self.logger.debug('Command received')
                msg = socket.recv_json()
                args = ''
                if 'cmd' not in msg:
                    socket.send_json({'status': STATUS_ERROR})
                    continue

                if msg['cmd'] == 'status':
                    args = self.get_status()
                    status = STATUS_OK
                elif msg['cmd'] == 'log_level':
                    if 'level' in msg and msg['level'] in ['debug', 'info', 'warning']:
                        self.manager.set_logging_level(msg['level'])
                        status = STATUS_OK
                    else:
                        status = STATUS_ERROR
                elif msg['cmd'] == 'notify_led':
                    if 'mode' in msg and msg['mode'] in ['red', 'green', 'orange']:
                        platform = self.manager.get_platform()
                        if isinstance(platform, bbb.Platform):
                            if msg['mode'] == 'red':
                                platform.notify_led(bbb.LED_MODE_FLASH_RED)
                            elif msg['mode'] == 'green':
                                platform.notify_led(bbb.LED_MODE_FLASH_GREEN)
                            else:
                                platform.notify_led(bbb.LED_MODE_FLASH_ORANGE)
                            status = STATUS_OK
                        else:
                            status = STATUS_ERROR
                    else:
                        status = STATUS_ERROR
                elif msg['cmd'] == 'shutdown':
                    socket.send_json({'status': STATUS_OK, 'args': args})
                    return
                else:
                    args = 'Unknown Command'
                    status = STATUS_ERROR

                socket.send_json({'status': status, 'args': args})
        self.logger.info('Stopping command processor')

    def stop(self):
        self.ev_stop = True

    def interface_config_to_string(self):
        cfg = self.manager.config
        interface = self.manager.interface
        network_mode = cfg.getint('network', 'mode')
        if network_mode == constants.NetworkIPMode.DHCP:
            return f'{interface} - DHCP'
        elif network_mode == constants.NetworkIPMode.STATIC:
            result = f'{interface} - Static ({cfg.get("network", "address")}/{cfg.get("network", "netmask")}'
            if cfg.get('network', 'gateway') is not None:
                result += f', GW {cfg.get("network", "gateway")}'
            if cfg.get('network', 'dns') is not None:
                result += f', DNS {cfg.get("network", "dns")}'
        else:
            return f'{interface} - Unconfigured'

    def get_status(self):
        # Returns the current sensor status as a human-readable string
        cfg = self.manager.config
        time_format = '%Y-%m-%d %H:%M:%S'
        last_successful_poll = 'Never' if polling._last_successful_poll is None else datetime.datetime.fromtimestamp(polling._last_successful_poll).strftime(time_format)
        return f'''--- Sensor status ---
Sensor manager: {importlib.metadata.version('honeysens-manager')}
Platform: {self.manager.platform.get_type()}@{self.manager.platform.get_architecture()}, Revision {self.manager.platform.get_current_revision()}
Sensor ID: {cfg.get('general', 'sensor_id')}
Hostname: {cfg.get('general', 'hostname')}
Server: {cfg.get('server', 'host')} / {cfg.get('server', 'name')} (Port {cfg.get('server', 'port_https')})
Network config: {self.interface_config_to_string()}
Polling interval: {cfg.get('server', 'interval')} minutes

System time: {datetime.datetime.now().strftime(time_format)}
Last successful poll: {last_successful_poll}
System update running: {self.manager.platform.is_firmware_update_in_progress()}
Service update running: {self.manager.platform.is_service_update_in_progress()}
Queued events: {len(self.manager.event_processor.events)}
Services:\n{pprint.pformat(services._services)}
'''
