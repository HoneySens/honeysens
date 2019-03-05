#!/usr/bin/env python2

from __future__ import absolute_import

import argparse
import zmq

from .utils import constants


def main():
    context = zmq.Context()
    parser = argparse.ArgumentParser()
    parser.add_argument('-s', '--shutdown', action='store_true', help='Cleanly shut the manager down (as with SIGTERM)')
    args = parser.parse_args()

    print('Connecting to socket {}'.format(constants.CMD_SOCKET))
    socket = context.socket(zmq.REQ)
    socket.connect(constants.CMD_SOCKET)

    if args.shutdown:
        socket.send_json({'cmd': 'shutdown'})
        response = socket.recv_json()
        print(response['response'])
    else:
        socket.send_json({'cmd': 'status'})
        response = socket.recv_json()
        print(response['response'])


if __name__ == '__main__':
    main()
