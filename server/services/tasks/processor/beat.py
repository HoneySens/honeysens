from celery.schedules import crontab
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
        logger.warning('Skipping beat: Could not connect to DB ({})'.format(str(e)))
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
    sender.add_periodic_task(60.0, check_sensor_timeout.s(), queue='high')
    sender.add_periodic_task(crontab(minute=1, hour=0), clean_api_log.s(), queue='low')
    sender.add_periodic_task(crontab(minute=0, hour=9, day_of_week='mon'), summarize_week.s(), queue='low')
    sender.add_periodic_task(crontab(minute=0, hour=9), check_ca_expiration.s(), queue='high')
    sender.add_periodic_task(crontab(minute=0, hour='*/4'), system_health_monitor.s(), queue='high')
    sender.add_periodic_task(crontab(minute=0, hour=3), archive_caretaker.s(), queue='low')


@processor.app.task
def check_sensor_timeout():
    perform_beat(constants.TaskType.SENSOR_TIMEOUT_CHECKER)


@processor.app.task
def summarize_week():
    perform_beat(constants.TaskType.WEEKLY_SUMMARIZER)


@processor.app.task
def check_ca_expiration():
    perform_beat(constants.TaskType.CA_EXPIRATION_CHECKER)


@processor.app.task
def system_health_monitor():
    perform_beat(constants.TaskType.SYSTEM_HEALTH_MONITOR)


@processor.app.task
def clean_api_log():
    perform_beat(constants.TaskType.API_LOG_CLEANER)


@processor.app.task
def archive_caretaker():
    perform_beat(constants.TaskType.ARCHIVE_CARETAKER)
