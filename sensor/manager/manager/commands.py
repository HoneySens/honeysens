from __future__ import absolute_import

import colorama
import sys
import zmq

from .utils import constants


def start(zmq_context, manager):
    colorama.init()
    socket = zmq_context.socket(zmq.REP)
    socket.bind(constants.CMD_SOCKET)

    while True:
        msg = socket.recv_json()
        do_shutdown = False
        if 'cmd' not in msg:
            socket.send_json({'status': 'error'})
        if msg['cmd'] == 'status':
            response = 'H{}o{}neySens Sensor Manager\nVersion 1.0.0'.format(colorama.Fore.RED, colorama.Fore.RESET)
        elif msg['cmd'] == 'shutdown':
            manager.shutdown()
            do_shutdown = True
            response = 'done'
        else:
            response = 'Unknown Command'
        socket.send_json({'status': 'ok', 'response': response})
        if do_shutdown:
            sys.exit(0)
