import csv
import json
import os
import pymysql
import shutil

from .handler import HandlerInterface


class EventExtractor(HandlerInterface):

    def perform(self, logger, db, config_path, storage_path, working_dir, job_data):
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
        result_dir = '{}/{}'.format(storage_path, job_data['id'])
        if os.path.isdir(result_dir):
            logger.debug('Result directory exists, removing it')
            shutil.rmtree(result_dir)
        logger.debug('Creating result directory {}'.format(result_dir))
        os.makedirs(result_dir)
        result_filename = 'result.csv'
        with open('{}/{}'.format(result_dir, result_filename), 'w') as f:
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