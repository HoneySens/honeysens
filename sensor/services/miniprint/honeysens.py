import os
import time
import zmq
import re

class HoneySensLogger(object):

    def __init__(self):

        self.disabled = False
        self.collector_host = None
        self.collector_port = None
        self.messages = []
        self.zmq_context = zmq.Context()

        if 'COLLECTOR_HOST' not in os.environ or 'COLLECTOR_PORT' not in os.environ:
            print('Error: No HoneySens collector specified, logging module disabled')
            self.disabled = True
        else:
            self.collector_host = os.environ['COLLECTOR_HOST']
            self.collector_port = os.environ['COLLECTOR_PORT']
            print('HoneySens collector available at tcp://{}:{}'.format(self.collector_host, self.collector_port))

    def log(self, message):

        if self.disabled:
            return
        
        self.messages.append({'timestamp': int(time.time()), 'data': message, 'type': 1})

        if "handle - close_conn - " not in message:
            return
        
        source = re.split(" - ", message)[2]
        event = {'timestamp': int(time.time()), 'source': source, 'service': 1,
                 'summary': 'Printer/PJL', 'details': self.messages, 'packets': []}
        self.messages = []

        # Collector connection
        socket = self.zmq_context.socket(zmq.REQ)
        # TODO Error handling
        socket.connect("tcp://{}:{}".format(self.collector_host, self.collector_port))
        socket.send_json(event)
        # TODO This BLOCKS in case there is no response (e.g. error on collector)
        socket.recv()
        # Cleanup
        socket.close()
    
    def debug(self, message):
        self.log(message)
    
    def info(self, message):
        self.log(message)
    
    def error(self, message):
        self.log(message)

    def log_session(self, session):
        pass
