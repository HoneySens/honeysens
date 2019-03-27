""" Thread collecting and compressing single events via ZeroMQ

Message format (JSON):
  {
    'timestamp': <unix timestamp>
    'service': <service id>
    'source': <event source IP>
    'summary': <event summary string>
    'details':
        [{
            'timestamp': <unix timestamp>
            'data': <detail description string>
            'type': <detail type (int)>
        }, ...],
    'packets':
        [{
            'headers':
            [{
                'flags': <set flags (string)>
            }],
            'protocol': <protocol id (int)>
            'port': <TCP/UDP port number (int)>
            'timestamp': <unix timestamp>
            'payload': <payload string>
        }, ...]
  }
"""
from __future__ import absolute_import

import logging
import netifaces
import threading
import zmq

from .utils import constants


class Collector(threading.Thread):

    ev_restart = threading.Event()
    ev_stop = threading.Event()
    logger = None
    platform = None
    queue = None
    zmq_context = None

    def __init__(self, zmq_context, platform, queue, hook_mgr):
        threading.Thread.__init__(self)
        self.ev_restart.set()
        self.platform = platform
        self.queue = queue
        self.zmq_context = zmq_context
        self.logger = logging.getLogger(__name__)
        self.logger.info('Initializing collector')
        hook_mgr.register_hook(constants.Hooks.ON_SERVICE_NETWORK_CHANGE, self.restart)

    def run(self):
        socket = self.zmq_context.socket(zmq.REP)
        poller = zmq.Poller()

        while self.ev_restart.is_set():
            try:
                binding_ip = netifaces.ifaddresses(self.platform.get_services_network_iface())[2][0]['addr']
                self.logger.info('Listening on tcp://{}:{}'.format(binding_ip, constants.COLLECTOR_PORT))
                socket.bind('tcp://{}:{}'.format(binding_ip, constants.COLLECTOR_PORT))
                poller.register(socket, zmq.POLLIN)
            except ValueError as e:
                self.logger.error('Collector couldn\'t be started ({})'.format(str(e)))
                return

            while not self.ev_stop.is_set():
                socks = dict(poller.poll(1000))
                if socks.get(socket) == zmq.POLLIN:
                    self.logger.debug('Event received')
                    msg = socket.recv_json()
                    self.queue.put(msg)
                    socket.send_json({'status': 'ok'})
                    # socket.send_json({'status': 'err', 'response': str(e)})
            socket.unbind('tcp://{}:{}'.format(binding_ip, constants.COLLECTOR_PORT))
            self.ev_stop.clear()
        self.logger.info('Stopping collector')

    def restart(self):
        self.ev_restart.set()
        self.ev_stop.set()

    def stop(self):
        self.ev_restart.clear()
        self.ev_stop.set()
