import os
import time
import zmq


# TODO IPMI traffic on udp/623 is visible, but doesn't trigger an event
# TODO Traffic on udp/161 doesn't trigger any response


class HoneySensLogger(object):
    def __init__(self):
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

    def log(self, event):
        if self.disabled:
            return

        # Split message data into chunks, server only accepts 255 bytes per entry
        messages = []
        request_msgs = []
        response_msgs = []
        data_length = 240
        data_type = event['data_type']
        request = event['data'].get('request')
        if request is not None:
            request_msgs = [request[i:i + data_length] for i in range(0, len(request), data_length)]
        response = event['data'].get('response')
        if response is not None:
            response_msgs = [response[i:i + data_length] for i in range(0, len(response), data_length)]

        for rm in request_msgs:
            messages.append({'timestamp': int(time.time()), 'data': 'Request: {}'.format(rm), 'type': 1})
        for rm in response_msgs:
            messages.append({'timestamp': int(time.time()), 'data': 'Response: {}'.format(rm), 'type': 1})
        messages.append({'timestamp': int(time.time()), 'data': 'Data type: {}'.format(data_type), 'type': 1})
        messages.append(
            {'timestamp': int(time.time()), 'data': 'Event type: {}'.format(event['data'].get('type')), 'type': 1})

        event = {'timestamp': int(time.time()), 'source': event['remote'][0], 'service': 1,
                 'summary': 'ICS ({})'.format(data_type), 'details': messages, 'packets': []}

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
