import logging
import zmq

from .platforms import bbb
from .utils import constants

STATUS_OK = 0
STATUS_ERROR = 1


class CommandProcessor():

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
                    args = 'HoneySens Sensor Manager is ready'
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
