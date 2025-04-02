import configparser
from datetime import datetime

from ..common import emails, templates
from .handler import HandlerInterface


class SensorTimeoutChecker(HandlerInterface):
    """Queries the database for all sensors and evaluates whether any sensors didn't report in time.
       Refreshes the status of all sensors that hit their timeout."""

    STATUS_TIMEOUT = 3

    def perform(self, logger, db, config_path, storage_path, working_dir, job_data):
        logger.info('Checking sensor timeouts')
        sensors = {}
        # Get global sensor polling interval
        config = configparser.ConfigParser()
        config.read_file(open(config_path))
        global_interval = config.getint('sensors', 'update_interval')
        timeout_threshold = config.getint('sensors', 'timeout_threshold')
        # Fetch sensors and their associated status data
        with db.cursor() as cur:
            cur.execute(('SELECT s.id,s.name,s.division_id,s.updateInterval,l.id,l.timestamp,l.status FROM sensors AS s ' 
                         'INNER JOIN statuslogs as l ON s.id = l.sensor_id'))
            rows = cur.fetchall()
            for row in rows:
                sensors.setdefault(row['id'], [])
                sensors[row['id']].append(
                    {'id': row['l.id'],
                     'name': row['name'],
                     'division': row['division_id'],
                     'ts': row['timestamp'],
                     'status': row['status'],
                     'interval': row['updateInterval']})
        # Evaluate each sensor
        # To be in sync with the PHP API and mysql default timezone ('SYSTEM'), which both use UTC, we don't attach a TZ
        now = datetime.now()
        for s_id, s_status in sensors.items():
            last_status = self._get_last_status(s_status)
            # If the last status indicates a timeout, we don't need to add another one
            if last_status['status'] == self.STATUS_TIMEOUT:
                continue
            # Check polling interval
            interval = last_status['interval'] if last_status['interval'] is not None else global_interval
            if (now - last_status['ts']).total_seconds() / 60 > interval + timeout_threshold:
                # Timeout and threshold exceeded, register timeout
                logger.info('Registering timeout for sensor {} (threshold of {} min)'.format(s_id, timeout_threshold))
                with db.cursor() as cur:
                    sql = 'INSERT INTO statuslogs (sensor_id, timestamp, status) VALUES (%s, %s, %s)'
                    cur.execute(sql, (s_id, now.strftime('%Y-%m-%d %H:%M:%S'), self.STATUS_TIMEOUT))
                    cur.execute('UPDATE last_updates SET timestamp = NOW() WHERE table_name = "sensors"')
                    db.commit()
                    # If e-mail notifications are enabled, find contacts and inform them
                    if not config.getboolean('smtp', 'enabled'):
                        continue
                    cur.execute(('SELECT c.email,u.email FROM contacts AS c LEFT JOIN users AS u ON c.user_id = u.id ' 
                                 'WHERE c.division_id = %s AND c.sendSensorTimeouts = 1'), last_status['division'])
                    for row in cur.fetchall():
                        recipient = row['u.email'] if row['u.email'] is not None else row['email']
                        s_name = last_status['name']
                        logger.info('Sending timeout notification for sensor {} (ID {}) to {}'.format(s_name, s_id,
                                                                                                      recipient))
                        self._notify_contact(db, config, recipient, s_id, s_name, interval, timeout_threshold)
        return {}

    @staticmethod
    def _get_last_status(status_data):
        """Takes a list of status dicts and returns the one with the youngest timestamp in the 'ts' field."""
        candidate = None
        for s in status_data:
            if candidate is None:
                candidate = s
            else:
                if s['ts'] > candidate['ts']:
                    candidate = s
        return candidate

    @staticmethod
    def _notify_contact(db, config, recipient, sensor_id, sensor_name, interval, timeout_threshold):
        """Sends an e-mail to recipient, notifying that the given sensor exceeded its timeout and is now offline."""
        subject = 'HoneySens: Sensor {} (ID {}) offline'.format(sensor_name, sensor_id)
        body = templates.process_template(db, templates.TemplateType.EMAIL_SENSOR_TIMEOUT, {
            'SENSOR_NAME': sensor_name,
            'SENSOR_ID': sensor_id,
            'UPDATE_INTERVAL': interval,
            'TIMEOUT_TOLERANCE': timeout_threshold
        })
        try:
            emails.send_email(config, recipient, subject, body)
        except Exception:
            pass
