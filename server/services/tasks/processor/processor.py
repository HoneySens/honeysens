#!/usr/bin/env python3

from . import constants
from .common.templates import SYSTEM_NOTIFICATION_TEMPLATES
from celery import (
    bootsteps,
    Celery
)
from click import Option
import json
from kombu import Queue
import logging
import os
import redis
import time

# Global vars
config_path = None
storage_path = '{}/{}'.format(constants.STORAGE_PATH, constants.RESULT_PATH)

# Local vars
_logger = logging.getLogger(__name__)


class ConfigBootstep(bootsteps.Step):
    """Prepare the execution environment."""
    def __init__(self, parent, hsconfig, **options):
        super().__init__(parent, **options)
        try:
            global config_path
            config_path = hsconfig
            _logger.info('Registering config file {}'.format(hsconfig))
            _logger.info('Initializing storage {}'.format(storage_path))
            while not os.path.exists(storage_path):
                _logger.info('Attempting to create storage directory {}'.format(storage_path))
                try:
                    os.makedirs(storage_path)
                except Exception:
                    _logger.info('Could not create storage directory, waiting for permission...')
                    time.sleep(10)
        except Exception as e:
            _logger.critical('Initialization error ({})'.format(str(e)))
            exit()


class NotificationTemplateInstaller(bootsteps.StartStopStep):
    requires = {'celery.worker.consumer.connection:Connection'}

    def start(self, parent):
        _logger.info('Pushing default notification templates to broker')
        r = redis.Redis(host=os.environ['BROKER_HOST'], port=os.environ['BROKER_PORT'])
        r.set('templates', json.dumps(SYSTEM_NOTIFICATION_TEMPLATES))


app = Celery('processor', broker='redis://{}:{}'.format(os.environ['BROKER_HOST'], os.environ['BROKER_PORT']), include=['processor.tasks', 'processor.beat'])
app.user_options['worker'].add(Option(['--hsconfig'], required=True, help='HoneySens configuration file path'))
app.steps['worker'].add(ConfigBootstep)
app.steps['consumer'].add(NotificationTemplateInstaller)

# Task queues for different priorities
app.conf.broker_transport_options = {
    'queue_order_strategy': 'sorted'
}
app.conf.task_queues = [Queue('high'), Queue('low')]

if __name__ == '__main__':
    app.start()
