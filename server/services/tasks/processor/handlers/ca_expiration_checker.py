import configparser
from datetime import datetime
from cryptography import x509
from cryptography.hazmat.backends import default_backend

from .. import constants
from ..common import emails, templates
from .handler import HandlerInterface


class CAExpirationChecker(HandlerInterface):
    """Parses the expiration date of the internal CA certificate and notifies users in case that date is approaching."""

    NOTIFY_DAYS_UNTIL_EXPIRATION = [1, 3, 7, 14, 28]

    def perform(self, logger, db, config_path, storage_path, working_dir, job_data):
        ca_crt_path = '{}/CA/ca.crt'.format(constants.STORAGE_PATH)
        with open(ca_crt_path, 'rb') as f:
            ca_crt_raw = f.read()
            ca_crt = x509.load_pem_x509_certificate(ca_crt_raw, default_backend())
        expires_in = ca_crt.not_valid_after - datetime.now()
        if expires_in.days in self.NOTIFY_DAYS_UNTIL_EXPIRATION:
            logger.info('CA certificate is about to expire in {} days'.format(expires_in.days))
            # Prepare config
            config = configparser.ConfigParser()
            config.read_file(open(config_path))
            # Find users who want to be notified
            with db.cursor() as cur:
                cur.execute('SELECT email FROM users WHERE notifyOnCAExpiration = 1')
                for row in cur.fetchall():
                    recipient = row['email']
                    logger.info('Sending expiration notice to {}'.format(recipient))
                    self._notify_user(db, config, recipient, expires_in.days)

    @staticmethod
    def _notify_user(db, config, recipient, expiration_days):
        server_name = config.get('server', 'host')
        subject = 'HoneySens: CA-Zertifikat des Servers {} läuft in {} Tagen ab'.format(server_name, expiration_days)
        body = templates.process_template(db, templates.TemplateType.EMAIL_CA_EXPIRATION, {
            'SERVER_NAME': server_name,
            'EXPIRATION_TIME': expiration_days
        })
        try:
            emails.send_email(config, recipient, subject, body)
        except Exception:
            pass
