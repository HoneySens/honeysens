from __future__ import absolute_import

import colorama
import zmq

from .utils import constants


def start(zmq_context):
    colorama.init()
    socket = zmq_context.socket(zmq.REP)
    socket.bind(constants.CMD_SOCKET)

    while True:
        msg = socket.recv_json()
        response = ''
        if 'cmd' not in msg:
            socket.send_json({'status': 'error'})
        if msg['cmd'] == 'status':
            response = 'H{}o{}neySens Sensor Manager\nVersion 1.0.0'.format(colorama.Fore.RED, colorama.Fore.RESET)
        socket.send_json({'status': 'ok', 'response': response})
