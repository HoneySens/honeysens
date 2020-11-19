from celery.utils.log import get_task_logger
import json
import os
import pymysql
import shutil
import tempfile
import traceback
from . import (
    constants,
    processor
)
from .handlers.event_extractor import EventExtractor
from .handlers.event_forwarder import EventForwarder
from .handlers.registry_manager import RegistryManager
from .handlers.sensorcfg_creator import SensorConfigCreator
from .handlers.upload_verifier import UploadVerifier
from .handlers.sensor_timeout_checker import SensorTimeoutChecker

handlers = {
    constants.TaskType.EVENT_EXTRACTOR: EventExtractor(),
    constants.TaskType.EVENT_FORWARDER: EventForwarder(),
    constants.TaskType.REGISTRY_MANAGER: RegistryManager(),
    constants.TaskType.SENSORCFG_CREATOR: SensorConfigCreator(),
    constants.TaskType.UPLOAD_VERIFIER: UploadVerifier(),
    constants.TaskType.SENSOR_TIMEOUT_CHECKER: SensorTimeoutChecker()
}
logger = get_task_logger(__name__)


def connect_to_db():
    """Initialize and return database connection."""
    try:
        db = pymysql.connect(host=os.environ['DB_HOST'], port=int(os.environ['DB_PORT']),
                             user=os.environ['DB_USER'], passwd=os.environ['DB_PASSWORD'],
                             db=os.environ['DB_NAME'], cursorclass=pymysql.cursors.DictCursor)
        return db
    except Exception as e:
        logger.warning('Could not connect to database ({})'.format(e))


def fetch_job_data(db, task_id):
    try:
        cursor = db.cursor()
        cursor.execute('SELECT * FROM tasks WHERE id = "{}"'.format(task_id))
        result = cursor.fetchone()
        cursor.close()
        if result is None:
            raise Exception('Job ID {} not found in database'.format(pymysql.escape_string(task_id)))
        else:
            return result
    except Exception as e:
        logger.warning('Database access error ({})'.format(e))


def delete_job(db, task_id):
    cursor = db.cursor()
    cursor.execute('DELETE FROM tasks WHERE id = "{}"'.format(task_id))
    cursor.execute('UPDATE last_updates SET timestamp = NOW() WHERE table_name = "tasks"')
    db.commit()
    cursor.close()


def update_job_status(db, task_id, status):
    cursor = db.cursor()
    cursor.execute('UPDATE tasks SET status = "{}" WHERE id = "{}"'.format(status, task_id))
    cursor.execute('UPDATE last_updates SET timestamp = NOW() WHERE table_name = "tasks"')
    db.commit()
    cursor.close()


def store_job_result(db, task_id, result):
    cursor = db.cursor()
    cursor.execute('UPDATE tasks SET result = "{}" WHERE id = "{}"'.format(pymysql.escape_string(json.dumps(result)), task_id))
    db.commit()
    cursor.close()


def perform_task(task, task_id, task_type):
    try:
        db = connect_to_db()
    except Exception as e:
        logger.warning('Could not connect to DB, rescheduling task {}'.format(task_id))
        # FIXME Direct queue assignment as workaround, otherwise tasks submitted via celery-php will cause an exception
        raise task.retry(exc=e, queue='low')
    working_dir = tempfile.mkdtemp()
    job_data = {}
    try:
        job_data = fetch_job_data(db, task_id)
        if job_data['status'] == constants.TaskStatus.SCHEDULED:
            update_job_status(db, task_id, constants.TaskStatus.RUNNING)
        else:
            raise Exception('Invalid job status {}'.format(job_data['status']))
        logger.debug('Performing task {}'.format(task_id))
        result = handlers[task_type].perform(logger, db, processor.config_path, processor.storage_path, working_dir, job_data)
        store_job_result(db, task_id, result)
        update_job_status(db, task_id, constants.TaskStatus.DONE)
    except Exception as e:
        logger.warning('Job threw an exception ({})'.format(str(e)))
        traceback.print_exc()
        update_job_status(db, task_id, constants.TaskStatus.ERROR)
    if 'user_id' in job_data and job_data['user_id'] is None:
        logger.debug('Removing system task {}'.format(task_id))
        delete_job(db, task_id)
    shutil.rmtree(working_dir)
    db.close()


@processor.app.task(bind=True)
def extract_events(task, task_id):
    perform_task(task, task_id, constants.TaskType.EVENT_EXTRACTOR)


@processor.app.task(bind=True)
def upload_to_registry(task, task_id):
    perform_task(task, task_id, constants.TaskType.REGISTRY_MANAGER)


@processor.app.task(bind=True)
def create_sensor_config(task, task_id):
    perform_task(task, task_id, constants.TaskType.SENSORCFG_CREATOR)


@processor.app.task(bind=True)
def verify_upload(task, task_id):
    perform_task(task, task_id, constants.TaskType.UPLOAD_VERIFIER)


@processor.app.task(bind=True)
def forward_events(task, task_id):
    perform_task(task, task_id, constants.TaskType.EVENT_FORWARDER)