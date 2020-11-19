import os
import time
import datetime
import zmq

from heralding.reporting.base_logger import BaseLogger

class HoneySensLogger(BaseLogger):
    def __init__(self):
        super().__init__()
        self.disabled = False
        self.collector_host = None
        self.collector_port = None
        self.zmq_context = zmq.Context()

        if 'COLLECTOR_HOST' not in os.environ or 'COLLECTOR_PORT' not in os.environ:
            print('Error: No HoneySens collector specified, logging module disabled')
            self.disabled = True
        else:
            self.collector_host = os.environ['COLLECTOR_HOST']
            self.collector_port = os.environ['COLLECTOR_PORT']
            print('HoneySens collector available at tcp://{}:{}'.format(self.collector_host, self.collector_port))

    def log(self, data):
        
        if self.disabled:
            return

        event = {'timestamp': int(time.time()), 'source': data['source_ip'], 'service': 1,
                 'summary': data['protocol'], 'details': data['details'], 'packets': []}
        # Collector connection
        socket = self.zmq_context.socket(zmq.REQ)
        # TODO Error handling
        socket.connect("tcp://{}:{}".format(self.collector_host, self.collector_port))
        socket.send_json(event)
        # TODO This BLOCKS in case there is no response (e.g. error on collector)
        socket.recv()
        # Cleanup
        socket.close()

    def log_session(self, session):
        pass
   
    # enable for simple credential catching
    #def handle_auth_log(self, data):
    #    if 'username' in data and 'password' in data:
    #        data['details'] = []
    #        data['details'].append({'timestamp': int(time.mktime(datetime.datetime.strptime(data['timestamp'],"%Y-%m-%d %H:%M:%S.%f").timetuple())),
    #                                'data': "Authentication from {0}:{1}, with username: \"{2}\" and password: \"{3}\"".format(data['source_ip'], data['source_port'],
    #                                                                                                                data['username'], data['password']),
    #                                'type': 1})
    #        self.log(data)

    def handle_session_log(self, data):
        if 'auth_attempts' in data and data['auth_attempts']:
            data['details'] = []
            data['details'].append({'timestamp': int(time.mktime(datetime.datetime.strptime(data['timestamp'],"%Y-%m-%d %H:%M:%S.%f").timetuple())),
                                    'data': "Duration: \"{0} sec\", session_id: \"{1}\", source: \"{2}:{3}\", auth attempts: \"{4}\"".format(data['duration'], data['session_id'], data['source_ip'],
                                                                                                                                             data['source_port'], data['num_auth_attempts']),
                                    'type': 1})
 
            if all(key in data['auxiliary_data'] for key in ('client_version', 'recv_cipher', 'recv_mac', 'recv_compression')):
                data['details'].append({'timestamp': int(time.mktime(datetime.datetime.strptime(data['timestamp'],"%Y-%m-%d %H:%M:%S.%f").timetuple())),
                                        'data': "client_version: \"{0}\", recv_cipher: \"{1}\", recv_mac: \"{2}\", recv_compression: \"{3}\"".format(data['auxiliary_data']['client_version'], data['auxiliary_data']['recv_cipher'],
                                                                                                                                                     data['auxiliary_data']['recv_mac'], data['auxiliary_data']['recv_compression']),
                                        'type': 1})

            for attempts in data['auth_attempts']:
                data['details'].append({'timestamp': int(time.mktime(datetime.datetime.strptime(attempts['timestamp'],"%Y-%m-%d %H:%M:%S.%f").timetuple())),
                                        'data': "Authentication attempt with username: \"{0}\" and password: \"{1}\"".format(attempts['username'],attempts['password']),
                                        'type': 1})
            self.log(data)
