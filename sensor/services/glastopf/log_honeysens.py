import os
import time
import zmq

from glastopf.modules.reporting.auxiliary.base_logger import BaseLogger


class LogHoneysens(BaseLogger):

    def __init__(self, data_dir, work_dir, config="glastopf.cfg"):
        config = os.path.join(work_dir, config)
        BaseLogger.__init__(self, config)
        self.disabled = False
        self.collector_host = None
        self.collector_port = None
        self.zmq_context = zmq.Context()
        self.options = {
            'enabled': self.config.getboolean('honeysens', 'enabled')
        }

        if 'COLLECTOR_HOST' not in os.environ or 'COLLECTOR_PORT' not in os.environ:
            print('Error: No HoneySens collector specified, logging module disabled')
            self.disabled = True
        else:
            self.collector_host = os.environ['COLLECTOR_HOST']
            self.collector_port = os.environ['COLLECTOR_PORT']
            print('HoneySens collector available at tcp://{}:{}'.format(self.collector_host, self.collector_port))

    def insert(self, attack_event):
        if self.disabled:
            return

        message = "Glastopf: %(pattern)s attack method from %(source)s against %(host)s:%(port)s. [%(method)s %(url)s] v:%(version)s id:%(sensorid)s" % {
            'pattern': attack_event.matched_pattern,
            'source': ':'.join((attack_event.source_addr[0], str(attack_event.source_addr[1]))),
            'host': attack_event.sensor_addr[0],
            'port': attack_event.sensor_addr[1],
            'method': attack_event.http_request.request_verb,
            'url': attack_event.http_request.request_url,
            'version': attack_event.version,
            'sensorid': attack_event.sensorid,
        }
        event = {'timestamp': int(time.time()), 'source': attack_event.source_addr[0], 'service': 2, 'summary': 'Glastopf',
                 'details': [{'timestamp': int(time.time()), 'data': message, 'type': 1}], 'packets': []}
        # Collector connection
        socket = self.zmq_context.socket(zmq.REQ)
        # TODO Error handling
        socket.connect("tcp://{}:{}".format(self.collector_host, self.collector_port))
        socket.send_json(event)
        # TODO This BLOCKS in case there is no response (e.g. error on collector)
        socket.recv()
        # Cleanup
        socket.close()
