import configparser
import os
import redis

from .handler import HandlerInterface
from ..common import emails, templates


class SystemHealthMonitor(HandlerInterface):

    def perform(self, logger, db, config_path, storage_path, working_dir, job_data):
        # Check broker queue lengths
        env_worker_count = os.environ['HS_WORKER_COUNT']
        threshold = 2 * os.cpu_count() if  env_worker_count == "auto" else 2 * int(env_worker_count)
        r = redis.Redis(host=os.environ['HS_BROKER_HOST'], port=os.environ['HS_BROKER_PORT'])
        queue_length = max(r.llen("high"), r.llen("low"))
        if queue_length > threshold:
            logger.info('System load is high, current queue length is {}'.format(queue_length))
            # Prepare config
            config = configparser.ConfigParser()
            config.read_file(open(config_path))
            # Find users who want to be notified
            with db.cursor() as cur:
                cur.execute('SELECT email FROM users WHERE notifyOnSystemState = 1')
                for row in cur.fetchall():
                    recipient = row['email']
                    logger.info('Sending expiration notice to {}'.format(recipient))
                    self._notify_user(db, config, recipient, queue_length, threshold)


    @staticmethod
    def _notify_user(db, config, recipient, queue_length, queue_threshold):
        server_name = config.get('server', 'host')
        subject = 'HoneySens: Hohe Systemlast auf Server {}'.format(server_name)
        body = templates.process_template(db, templates.TemplateType.EMAIL_HIGH_SYSTEM_LOAD, {
            'SERVER_NAME': server_name,
            'QUEUE_LENGTH': queue_length,
            'QUEUE_THRESHOLD': queue_threshold
        })
        try:
            emails.send_email(config, recipient, subject, body)
        except Exception:
            pass
