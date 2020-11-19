from celery.utils.log import get_task_logger
import traceback
from . import (
    constants,
    processor,
    tasks
)

logger = get_task_logger(__name__)


def perform_beat(task_type):
    try:
        db = tasks.connect_to_db()
    except Exception as e:
        logger.warning('Could not connect to DB, skipping beat {}'.format(task_type))
        return
    try:
        # Beats receive no working directory and no additional job data
        tasks.handlers[task_type].perform(logger, db, processor.config_path, processor.storage_path, None, None)
    except Exception as e:
        logger.warning('Beat threw an exception ({})'.format(str(e)))
        traceback.print_exc()
    db.close()


@processor.app.on_after_finalize.connect
def setup_periodic_tasks(sender, **kwargs):
    sender.add_periodic_task(60.0, check_sensor_timeout.s(), queue='low')


@processor.app.task
def check_sensor_timeout():
    perform_beat(constants.TaskType.SENSOR_TIMEOUT_CHECKER)
