import configparser
from datetime import datetime
import json
import logging
import logging.handlers
import socket

from .handler import HandlerInterface

CLASSIFICATIONS = {0: 'unknown', 1: 'icmp', 2: 'connection_attempt', 3: 'honeypot', 4: 'scan'}
PRIORITIES = {2: 'critical', 3: 'error', 4: 'warning', 6: 'info', 7: 'debug'}
STATUS = {0: 'new', 1: 'busy', 2: 'resolved', 3: 'ignored'}


class EventForwarder(HandlerInterface):

    logger = None

    def __init__(self):
        self.logger = logging.getLogger('ext_syslog')
        self.logger.setLevel(logging.DEBUG)
        self.logger.propagate = False

    def perform(self, logger, db, config_path, storage_path, working_dir, job_data):
        """Depending on the provided params, this task either forwards task data directly or retrieves it from the
        db. """
        job_params = json.loads(job_data['params'])
        event = None
        if 'id' in job_params:
            # Fetch event from db
            cursor = db.cursor()
            cursor.execute('SELECT * FROM events WHERE id = {}'.format(job_params['id']))
            event = cursor.fetchone()
            if event:
                # Fetch further sensor data
                cursor.execute('SELECT name FROM sensors where id = {}'.format(event['sensor_id']))
                event['timestamp'] = int(datetime.timestamp(event['timestamp']))
                event['sensor_name'] = cursor.fetchone()['name']
            cursor.close()
        elif 'event' in job_params:
            # Take event directly from job params
            event = job_params['event']
        if event is None:
            raise Exception('No event data available')
        # Parse event args into a more human-readable format
        event['classification'] = CLASSIFICATIONS[event['classification']]
        event['status'] = STATUS[event['status']]
        del event['service']
        # Instantiate syslog forwarder
        config = configparser.ConfigParser()
        config.read_file(open(config_path))
        handler = logging.handlers.SysLogHandler(address=(config.get('syslog', 'server'), config.getint('syslog', 'port')),
                                                 facility=config.getint('syslog', 'facility'),
                                                 socktype=socket.SOCK_DGRAM if config.getint('syslog', 'transport') == 0 else socket.SOCK_STREAM)
        self.logger.addHandler(handler)
        # Call logger based on priority
        log_fnc = getattr(self.logger, PRIORITIES[config.getint('syslog', 'priority')])
        log_fnc(json.dumps(event))
        self.logger.removeHandler(handler)
        # TODO remove task from DB
