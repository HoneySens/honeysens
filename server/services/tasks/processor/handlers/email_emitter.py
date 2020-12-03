import configparser
import json

from ..common import emails
from .handler import HandlerInterface


class EMailEmitter(HandlerInterface):
    """Sends a mail according to the given parameters. Used to directly dispatch mails from the API."""

    def perform(self, logger, db, config_path, storage_path, working_dir, job_data):
        config = configparser.ConfigParser()
        config.read_file(open(config_path))
        job_params = json.loads(job_data['params'])
        if 'test_mail' in job_params:
            # Read SMTP configuration from job data
            if 'smtp_user' in job_params:
                smtp_user = job_params['smtp_user']
                smtp_password = job_params['smtp_password']
            else:
                smtp_user = None
                smtp_password = None
            try:
                emails.send_email_raw(job_params['smtp_server'], job_params['smtp_port'], job_params['from'],
                                      job_params['to'], job_params['subject'], job_params['body'], smtp_user=smtp_user,
                                      smtp_password=smtp_password, transport_security=job_params['smtp_encryption'])
            except Exception as e:
                return {'error': '{}: {}'.format(type(e), str(e))}
        else:
            try:
                emails.send_email(config, job_params['to'], job_params['subject'], job_params['body'])
            except Exception as e:
                return {'error': str(e)}
