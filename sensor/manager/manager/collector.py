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
            'port': <TCP/UDP port number (ingt)>
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

_logger = None


def worker(zmq_context, events, events_lock):
    socket = zmq_context.socket(zmq.REP)
    try:
        docker_bridge_ip = netifaces.ifaddresses(constants.DOCKER_BRIDGE)[2][0]['addr']
        socket.bind('tcp://{}:{}'.format(docker_bridge_ip, constants.COLLECTOR_PORT))
    except ValueError as e:
        _logger.error('Collector couldn\'t be started ({})'.format(str(e)))

    while True:
        msg = socket.recv_json()
        with events_lock:
            if msg['source'] not in events:
                events[msg['source']] = []
            events[msg['source']].append(msg)
            socket.send_json({'status': 'ok'})
            # socket.send_json({'status': 'err', 'response': str(e)})


def start(zmq_context, events, events_lock):
    global _logger
    _logger = logging.getLogger(__name__)
    _logger.info('Starting collector service')
    thread = threading.Thread(target=worker, args=(zmq_context, events, events_lock))
    # TODO Replace this with signalling and a graceful shutdown
    thread.daemon = True
    thread.start()

