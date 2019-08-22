#!/usr/bin/env python2

from __future__ import absolute_import

import argparse
import beanstalkc
import coloredlogs
import ConfigParser
import json
import logging
import os
import pymysql
import shutil
import signal
import sys
import tempfile
import threading
import traceback

from tasks import constants
from tasks.handlers import sensorcfg_creator, upload_verifier, registry_manager, event_extractor

processor = None


class TaskProcessor:

    beanstalk = None
    config = ConfigParser.ConfigParser()
    ev_stop = threading.Event()
    handlers = {}  # job_type(int) -> handler_instance(obj)
    logger = None
    storage_path = None

    def __init__(self, config_path, log_lvl):
        # Force UTF8 encoding to properly consume parameter strings
        reload(sys)
        sys.setdefaultencoding('utf-8')
        self.init_logging(log_lvl)
        self.init_config(config_path)
        self.init_storage('{}/{}'.format(self.config.get('server', 'data_path'), constants.RESULT_PATH))
        self.init_beanstalk()
        self.init_handlers()

    def init_logging(self, log_lvl):
        self.logger = logging.getLogger(__name__)
        coloredlogs.install(level=log_lvl.upper(), fmt='%(asctime)s [%(name)s] %(levelname)s %(message)s')
        self.logger.info('Starting up...')
        self.logger.info('Log level: {}'.format(log_lvl))

    def init_config(self, config_path):
        if not os.path.isfile(config_path):
            self.logger.critical('Could not open configuration file {}'.format(config_path))
            exit()
        try:
            self.config.readfp(open(config_path))
        except Exception as e:
            self.logger.critical('Could not parse configuration ({})'.format(str(e)))
            exit()

    def init_beanstalk(self):
        try:
            self.beanstalk = beanstalkc.Connection(host=self.config.get('beanstalkd', 'host'), port=int(self.config.get('beanstalkd', 'port')))
            self.beanstalk.watch('honeysens')
        except Exception as e:
            self.logger.critical('Could not connect to beanstalk daemon ({})'.format(str(e)))
            exit()

    def init_storage(self, path):
        if not os.path.exists(path):
            self.logger.debug('Creating storage directory {}'.format(path))
            os.makedirs(path)
        self.storage_path = path
        self.logger.info('Task result storage directory: {}'.format(path))

    def init_handlers(self):
        self.handlers[constants.TaskType.SENSORCFG_CREATOR] = sensorcfg_creator.SensorConfigCreator(self.config, self.storage_path)
        self.handlers[constants.TaskType.UPLOAD_VERIFIER] = upload_verifier.UploadVerifier(self.config, self.storage_path)
        self.handlers[constants.TaskType.REGISTRY_MANAGER] = registry_manager.RegistryManager(self.config, self.storage_path)
        self.handlers[constants.TaskType.EVENT_EXTRACTOR] = event_extractor.EventExtractor(self.config, self.storage_path)

    def fetch_job_data(self, id, db):
        cursor = db.cursor(pymysql.cursors.DictCursor)
        cursor.execute('SELECT * FROM tasks WHERE id = "{}"'.format(id))
        result = cursor.fetchone()
        cursor.close()
        if result is None:
            raise Exception('Job ID {} not found in database'.format(pymysql.escape_string(id)))
        else:
            return result

    def update_job_status(self, id, db, status):
        cursor = db.cursor()
        cursor.execute('UPDATE tasks SET status = "{}" WHERE id = "{}"'.format(status, id))
        cursor.execute('UPDATE last_updates SET timestamp = NOW() WHERE table_name = "tasks"')
        db.commit()
        cursor.close()

    def store_job_result(self, id, db, result):
        cursor = db.cursor()
        cursor.execute('UPDATE tasks SET result = "{}" WHERE id = "{}"'.format(pymysql.escape_string(json.dumps(result)), id))
        db.commit()
        cursor.close()

    def start(self):
        self.logger.info('Starting worker loop')
        while not self.ev_stop.is_set():
            job = self.beanstalk.reserve(1)
            if job is None:
                continue
            try:
                job_body = json.loads(job.body)
                assert 'id' in job_body
                job_id = job_body['id']
            except Exception:
                self.logger.warning('Invalid job data, removing job')
                job.delete()
                continue
            self.logger.info('New job received ({})'.format(job_id))
            # TODO Wait for database connection
            try:
                db = pymysql.connect(host=os.environ['DB_HOST'], port=int(os.environ['DB_PORT']),
                                     user=os.environ['DB_USER'], passwd=os.environ['DB_PASSWORD'],
                                     db=os.environ['DB_NAME'])
                job_data = self.fetch_job_data(job_id, db)
            except pymysql.err.OperationalError:
                self.logger.warning('Database access error, removing job')
                job.delete()
                continue
            except Exception:
                db.close()
                job.delete()
                continue
            # Refresh job status
            if job_data['status'] == constants.TaskStatus.SCHEDULED:
                self.update_job_status(job_id, db, constants.TaskStatus.RUNNING)
            else:
                self.logger.warning('Invalid job status {} ({})'.format(job_data['status'], job_id))
                db.close()
                job.delete()
                continue
            # Call appropriate handler if possible
            if job_data['type'] in self.handlers:
                working_dir = tempfile.mkdtemp()
                try:
                    result = self.handlers[job_data['type']].perform(db, working_dir, job_data)
                    self.store_job_result(job_id, db, result)
                    self.update_job_status(job_id, db, constants.TaskStatus.DONE)
                except Exception as e:
                    self.logger.warning('Job {} threw an exception ({})'.format(job_id, str(e)))
                    traceback.print_exc()
                    self.update_job_status(job_id, db, constants.TaskStatus.ERROR)
                shutil.rmtree(working_dir)
                self.logger.info('Job {} completed'.format(job_id))
            else:
                self.logger.warning('Unknown job type {} ({})'.format(job_data['type'], job_id))
                self.update_job_status(job_id, db, constants.TaskStatus.ERROR)
            db.close()
            job.delete()
        self.logger.info('Shutdown complete')

    def shutdown(self):
        self.ev_stop.set()


def sigterm_handler(signal, frame):
    processor.logger.warning('Received SIGTERM, performing graceful shutdown')
    processor.shutdown()
    sys.exit(0)

def main():
    global processor
    parser = argparse.ArgumentParser()
    parser.add_argument('config', help='Server configuration file')
    parser.add_argument('-l', '--log-level', choices=['debug', 'info', 'warning'], default='info', help='Logging level')
    args = parser.parse_args()
    # Register signal handlers
    signal.signal(signal.SIGTERM, sigterm_handler)
    processor = TaskProcessor(args.config, args.log_level)
    processor.start()

if __name__ == '__main__':
    main()
