#!/usr/bin/env python2

from __future__ import absolute_import

import zmq

from .utils import constants


def main():
    context = zmq.Context()

    print('Connecting to socket {}'.format(constants.CMD_SOCKET))
    socket = context.socket(zmq.REQ)
    socket.connect(constants.CMD_SOCKET)

    socket.send_json({'cmd': 'status'})
    response = socket.recv_json()
    print(response['response'])


if __name__ == '__main__':
    main()
