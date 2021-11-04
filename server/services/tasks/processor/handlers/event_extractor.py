import csv
import json
import os
import pymysql
import shutil
from string import digits

from .handler import HandlerInterface


class EventExtractor(HandlerInterface):

    def perform(self, logger, db, config_path, storage_path, working_dir, job_data):
        job_params = json.loads(job_data['params'])
        sensors = self.fetch_sensors(db)
        cursor = db.cursor(pymysql.cursors.DictCursor)
        cursor.execute(job_params['query'])
        headers = ['id', 'sensor_id', 'sensor_name', 'timestamp', 'source', 'classification', 'summary', 'archived', 'status', 'comment']
        result = []
        for row in cursor.fetchall():
            result.append(self.generate_event_row(self.parse_db_event(row), headers, sensors))
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

    @staticmethod
    def fetch_sensors(db) -> dict:
        """Fetches and returns all sensors in an ID-indexed dict from the database."""
        result = {}
        cursor = db.cursor(pymysql.cursors.DictCursor)
        cursor.execute('SELECT * FROM sensors')
        for row in cursor.fetchall():
            result[row['id']] = row
        return result

    @staticmethod
    def parse_db_event(row):
        """Parses a raw event row into a dictionary, e.g. renaming keys from 'id0' to 'id'."""
        result = {}
        for key, val in row.items():
            result[key.translate(str.maketrans('', '', digits))] = val
        return result

    def generate_event_row(self, event: dict, headers: list, sensors: dict) -> list:
        """Takes a dictionary of parsed db event data and returns it in a serialized format structured by headers."""
        result = []
        ev = event.copy()
        archived = 'oid' in event.keys()
        ev['id'] = ev['oid'] if archived else ev['id']
        ev['archived'] = archived
        ev['classification'] = self.classification2string(ev['classification'])
        ev['status'] = self.status2string(ev['status'])
        ev['sensor_id'] = '' if archived else ev['sensor_id']
        ev['sensor_name'] = ev['sensor'] if archived else sensors[ev['sensor_id']]['name']
        for h in headers:
            if h in ev.keys():
                result.append(ev[h])
        return result

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