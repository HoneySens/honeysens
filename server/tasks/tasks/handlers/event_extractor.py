#!/usr/bin/env python2

import csv
import json
import logging
import os
import pymysql
import shutil

from tasks.handlers.handler import Handler


class EventExtractor(Handler):

    logger = None

    def __init__(self, config, storage_path):
        super(EventExtractor, self).__init__(config, storage_path)
        self.logger = logging.getLogger(__name__)
        self.logger.info('Event extractor initialized')

    def perform(self, db, working_dir, job_data):
        self.logger.debug('Performing job {}'.format(job_data['id']))
        job_params = json.loads(job_data['params'])
        cursor = db.cursor(pymysql.cursors.DictCursor)
        cursor.execute(job_params['query'])
        headers = ['id', 'sensor_id', 'timestamp', 'source', 'classification', 'summary', 'status', 'comment']
        result = []
        for row in cursor.fetchall():
            event = {}
            for k in row.keys():
                # Find an appropriate header field for each given key
                for h in headers:
                    if k[:-1] == h:
                        if h == 'classification':
                            event[h] = self.classification2string(row[k])
                        elif h == 'status':
                            event[h] = self.status2string(row[k])
                        else:
                            event[h] = row[k]
                        break
            event_serial = []
            for h in headers:
                event_serial.append(event[h])
            result.append(event_serial)
        # Store result
        result_dir = '{}/{}'.format(self.storage_path, job_data['id'])
        if os.path.isdir(result_dir):
            self.logger.debug('Result directory exists, removing it')
            shutil.rmtree(result_dir)
        self.logger.debug('Creating result directory {}'.format(result_dir))
        os.makedirs(result_dir)
        result_filename = 'result.csv'
        with open('{}/{}'.format(result_dir, result_filename), 'wb') as f:
            writer = csv.writer(f)
            writer.writerow(headers)
            writer.writerows(result)
        # 'path' is relative to the base result directory
        return {'path': result_filename}

    def classification2string(self, classification):
        if classification == 1:
            return 'ICMP'
        elif classification == 2:
            return 'Connection attempt'
        elif classification == 3:
            return 'Honeypot'
        elif classmethod == 4:
            return 'Portscan'
        else:
            return 'Unbekannt'

    def status2string(self, status):
        if status == 0:
            return 'Neu'
        elif status == 1:
            return 'In Bearbeitung'
        elif status == 2:
            return 'Erledigt'
        else:
            return 'Ignoriert'