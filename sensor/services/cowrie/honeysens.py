from __future__ import division, absolute_import

import os
import time
import zmq

import cowrie.core.output


class Output(cowrie.core.output.Output):

    def __init__(self, cfg):
        self.disabled = False
        self.collector_host = None
        self.collector_port = None
        self.sessions = {}
        self.zmq_context = None

        if 'COLLECTOR_HOST' not in os.environ or 'COLLECTOR_PORT' not in os.environ:
            print('Error: No HoneySens collector specified, logging module disabled')
            self.disabled = True
        else:
            self.collector_host = os.environ['COLLECTOR_HOST']
            self.collector_port = os.environ['COLLECTOR_PORT']
            print('HoneySens collector available at tcp://{}:{}'.format(self.collector_host, self.collector_port))

        cowrie.core.output.Output.__init__(self, cfg)

    def start(self):
        self.zmq_context = zmq.Context()

    def stop(self):
        self.zmq_context.close()

    def write(self, entry):
        if self.disabled:
            return

        if entry['eventid'] == 'cowrie.session.connect':
            messages = [
                {'timestamp': int(time.time()),
                 'data': 'New connection: {}:{}'.format(entry['src_ip'], entry['src_port']),
                 'type': 1}]
            self.sessions[entry['session']] = {'source': entry['src_ip'], 'messages': messages}

        elif entry['eventid'] == 'cowrie.login.success':
            session = entry['session']
            if session in self.sessions:
                self.sessions[session]['messages'].append({'timestamp': int(time.time()),
                                                           'data': 'Login succeeded [{}/{}]'.format(entry['username'],
                                                                                                    entry['password']),
                                                           'type': 1})

        elif entry['eventid'] == 'cowrie.login.failed':
            session = entry['session']
            if session in self.sessions:
                self.sessions[session]['messages'].append(
                    {'timestamp': int(time.time()),
                     'data': 'Login failed [{}/{}]'.format(entry['username'], entry['password']),
                     'type': 1})

        elif entry['eventid'] == 'cowrie.command.success':
            session = entry['session']
            if session in self.sessions:
                self.sessions[session]['messages'].append(
                    {'timestamp': int(time.time()), 'data': 'Command [{}]'.format(entry['input']), 'type': 1})

        elif entry['eventid'] == 'cowrie.command.failed':
            session = entry['session']
            if session in self.sessions:
                self.sessions[session]['messages'].append(
                    {'timestamp': int(time.time()), 'data': 'Unknown command [{}]'.format(entry['input']), 'type': 1})

        elif entry['eventid'] == 'cowrie.session.file_download':
            # TODO Either don't save outfile or (even better) forward it to the server
            session = entry['session']
            if session in self.sessions:
                self.sessions[session]['messages'].append(
                    {'timestamp': int(time.time()),
                     'data': 'File download: [{}] -> {} ({})'.format(entry['url'], entry['outfile'], entry['shasum']),
                     'type': 1})

        elif entry['eventid'] == 'cowrie.session.file_upload':
            # TODO Either don't save outfile or (even better) forward it to the server
            session = entry['session']
            if session in self.sessions:
                self.sessions[session]['messages'].append(
                    {'timestamp': int(time.time()),
                     'data': 'File upload: {} ({})'.format(entry['outfile'], entry['shasum']),
                     'type': 1})

        elif entry['eventid'] == 'cowrie.session.input':
            session = entry['session']
            if session in self.sessions:
                self.sessions[session]['messages'].append(
                    {'timestamp': int(time.time()), 'data:': 'Input [{}] @{}'.format(entry['input'], entry['realm']),
                     'type': 1})

        elif entry['eventid'] == 'cowrie.client.version':
            session = entry['session']
            if session in self.sessions:
                self.sessions[session]['messages'].append(
                    {'timestamp': int(time.time()), 'data': 'Client version: [{}]'.format(entry['version']), 'type': 1})

        elif entry['eventid'] == 'cowrie.client.size':
            session = entry['session']
            if session in self.sessions:
                self.sessions[session]['messages'].append(
                    {'timestamp': int(time.time()),
                     'data': 'Terminal size: {}x{}'.format(entry['width'], entry['height']),
                     'type': 1})

        elif entry['eventid'] == 'cowrie.log.closed':
            pass

        elif entry['eventid'] == 'cowrie.client.fingerprint':
            session = entry['session']
            if session in self.sessions:
                self.sessions[session]['messages'].append(
                    {'timestamp': int(time.time()),
                     'data': 'Fingerprint: {}'.format(entry['fingerprint']),
                     'type': 1})

        elif entry['eventid'] == 'cowrie.session.closed':
            session = entry['session']
            if session in self.sessions:
                self.sessions[session]['messages'].append({'timestamp': int(time.time()),
                                                           'data': 'Session closed',
                                                           'type': 1})
                messages = self.sessions[session]['messages']
                event = {'timestamp': messages[0]['timestamp'], 'source': self.sessions[session]['source'], 'service': 1,
                         'summary': 'SSH', 'details': messages, 'packets': []}
                # Collector connection
                socket = self.zmq_context.socket(zmq.REQ)
                # TODO Error handling
                socket.connect("tcp://{}:{}".format(self.collector_host, self.collector_port))
                socket.send_json(event)
                # TODO This BLOCKS in case there is no response (e.g. error on collector)
                socket.recv()
                # Cleanup
                del self.sessions[session]
                socket.close()
