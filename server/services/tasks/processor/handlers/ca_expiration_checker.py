import configparser
from datetime import datetime
from cryptography import x509
from cryptography.hazmat.backends import default_backend

from .. import constants
from ..common import emails
from .handler import HandlerInterface


class CAExpirationChecker(HandlerInterface):
    """Parses the expiration date of the internal CA certificate and notifies users in case that date is approaching."""

    NOTIFY_DAYS_UNTIL_EXPIRATION = [1, 3, 7, 14]

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
                    self._notify_user(config, recipient, expires_in.days)

    @staticmethod
    def _notify_user(config, recipient, expiration_days):
        server_name = config.get('server', 'host')
        subject = 'HoneySens: CA-Zertifikat des Servers {} läuft in {} Tagen ab'.format(server_name, expiration_days)
        body = ('Dies ist eine automatisch generierte Hinweismail des HoneySens-Servers {}.\n\n'
                'Das interne CA-Zertifikat läuft in {} Tagen ab und sollte zuvor unbedingt erneuert werden. '
                'Melden Sie sich hierzu mit einem administrativen Account an der Weboberfläche des Servers an und '
                'folgen Sie den Anweisungen unter "System" -> "Certificate Authority".\n\n'
                'Nach Ablauf des Zertifikats ohne rechtzeitige Verlängerung können die bestehenden Sensoren nicht mehr '
                'mit dem Server kommunizieren und müssen neu aufgesetzt werden.').format(server_name, expiration_days)
        try:
            emails.send_email(config, recipient, subject, body)
        except Exception:
            pass
