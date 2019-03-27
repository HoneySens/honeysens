from __future__ import absolute_import

import logging
import Queue
import re
import threading

from . import hooks
from . import services
from .utils import constants


class StateWorker(threading.Thread):

    ev_stop = None
    logger = None
    queue = None

    def __init__(self, queue):
        threading.Thread.__init__(self)
        self.ev_stop = threading.Event()
        self.queue = queue
        self.logger = logging.getLogger(__name__)
        self.logger.info('Initializing state worker')

    def run(self):
        while not self.ev_stop.is_set():
            try:
                state_config = self.queue.get(True, 1)
            except Queue.Empty:
                continue
            self.logger.debug('Configuration received')
            self.apply_config(state_config['config'], state_config['server_response'], state_config['network_changed'])
            self.logger.debug('Configuration application done, notifying queue')
            self.queue.task_done()
        self.logger.info('Stopping worker')

    def stop(self):
        self.ev_stop.set()

    def apply_config(self, config, server_response, network_changed):
        if network_changed:
            # Stop services
            services.stop_all()
            # Adjust /etc/hosts if necessary
            server_host = config.get('server', 'host')
            server_name = config.get('server', 'name')
            if self.is_ip(server_host):
                self.update_server_endpoint(server_host, server_name)
        hooks.execute_hook(constants.Hooks.ON_APPLY_CONFIG, [config, server_response, network_changed])

    def is_ip(self, value):
        parts = value.split('.')
        if len(parts) == 4 and all([x.isdigit() for x in parts]):
            numbers = list(int(x) for x in parts)
            return all([num >= 0 and num <= 255 for num in numbers])
        return False

    def update_server_endpoint(self, host, name):
        self.logger.info('Updating server endpoint in /etc/hosts')
        with open('/etc/hosts', 'rb') as f:
            hosts_content = f.read()
        if 'honeysens-server' in hosts_content:
            # Adjust existing hosts entry
            # TODO Only write if changes happened

            # We can't use the fileinput module here because that one physically moves file which is not possible
            # with /etc/hosts inside docker containers (it's a mounted file)
            hosts_content = re.sub('\d{1,3}.\d{1,3}.\d{1,3}.\d{1,3}\t\S+ honeysens-server', '{}\t{} honeysens-server'.format(host, name), hosts_content)
            with open('/etc/hosts', 'wb') as f:
                f.write(hosts_content)
        else:
            # Add new hosts entry
            with open('/etc/hosts', 'a') as f:
                f.write('{}\t{} {}\n'.format(host, name, 'honeysens-server'))
