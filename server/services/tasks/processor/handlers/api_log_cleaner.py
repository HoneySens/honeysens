import configparser
from datetime import datetime, timedelta

from .handler import HandlerInterface


class APILogCleaner(HandlerInterface):
    """Queries the database and removes all API log entries that are older than the user-specified 'keep' interval
       demands."""

    def perform(self, logger, db, config_path, storage_path, working_dir, job_data):
        # Get cleaning threshold
        config = configparser.ConfigParser()
        config.read_file(open(config_path))
        threshold_days = config.getint('misc', 'api_log_keep_days')
        if threshold_days > 0:
            threshold = datetime.now() - timedelta(days=threshold_days)
            with db.cursor() as cur:
                row_cnt = cur.execute('DELETE FROM logs WHERE timestamp <= DATE(NOW()) - INTERVAL {} DAY'.format(threshold_days))
                db.commit()
                if row_cnt > 0:
                    logger.info('Removed {} API log entries older than {}'.format(row_cnt, threshold.isoformat(
                        sep=' ', timespec='minutes')))
        return {}
