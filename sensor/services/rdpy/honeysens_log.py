import time
import os
import sys
import zmq

# Import common tools
sys.path.append('/opt/honeysens')
import utils

# One logger module per remote connection
class HoneySensLogger(object):

    def __init__(self, src_host, src_port):
        self.disabled = False
        self.collector_host = None
        self.collector_port = None
        self.messages = []
        self.src_host = src_host
        self.src_port = src_port
        self.zmq_context = zmq.Context()

        if 'COLLECTOR_HOST' not in os.environ or 'COLLECTOR_PORT' not in os.environ:
            print('Error: No HoneySens collector specified, logging module disabled')
            self.disabled = True
        else:
            self.collector_host = os.environ['COLLECTOR_HOST']
            self.collector_port = os.environ['COLLECTOR_PORT']
            print('HoneySens collector available at tcp://{}:{}'.format(self.collector_host, self.collector_port))
            self.log('Connection from {}:{}'.format(src_host, src_port))

    def log(self, message):
        self.messages.append({'timestamp': int(time.time()), 'data': message, 'type': 1})

    def log_credentials(self, domain, username, password, hostname):
        data = {}
        if domain:
            data['Domain'] = domain
        if username:
            data['Username'] = username
        if password:
            data['Password'] = password
        if hostname:
            data['Hostname'] = hostname
        self.log('[Credentials] ' + ', '.join([i[0] + ': ' + i[1] for i in data.items()]))

    def log_key(self, code, isPressed, isExtended):
        if isPressed:
            action = 'pressed'
        else:
            action = 'released'
        self.log('[Key Event] Key {} was {} (Extended: {})'.format(code, action, isExtended))

    def log_pointer(self, x, y, button, isPressed):
        # Ignore plain pointer movement
        if button == 0:
            return
        if isPressed:
            action = 'pressed'
        else:
            action = 'released'
        self.log('[Pointer Event] Mouse button {} was {} at X: {}, Y: {}'.format(button, action, x, y))

    def commit(self):
        if self.disabled:
            return

        self.messages.append({'timestamp': int(time.time()), 'data': 'Session closed', 'type': 1})
        event = {'timestamp': int(time.time()), 'source': self.src_host, 'service': 1,
                 'summary': 'RDPY', 'details': self.messages, 'packets': []}

        # Collector connection
        socket = self.zmq_context.socket(zmq.REQ)
        # TODO Error handling
        socket.connect("tcp://{}:{}".format(self.collector_host, self.collector_port))
        socket.send_json(event)
        # TODO This BLOCKS in case there is no response (e.g. error on collector)
        socket.recv()
        # Cleanup
        self.messages = []
        socket.close()

