import configparser
import json
import os
import subprocess
import tarfile

from .. import constants
from .handler import HandlerInterface


class RegistryManager(HandlerInterface):

    def perform(self, logger, db, config_path, storage_path, working_dir, job_data):
        config = configparser.ConfigParser()
        config.read_file(open(config_path))
        registry = '{}:{}'.format(os.environ['HS_REGISTRY_HOST'], os.environ['HS_REGISTRY_PORT'])
        upload_path = '{}/{}'.format(constants.STORAGE_PATH, constants.UPLOAD_PATH)
        logger.info('Registry endpoint: {}'.format(registry))
        logger.info('Registry manager initialized, expecting uploads in {}'.format(upload_path))
        job_params = json.loads(job_data['params'])
        uploaded_file = '{}/{}'.format(upload_path, job_params['path'])
        if not os.path.isfile(uploaded_file):
            raise Exception('Path {} does not exist or is not a file'.format(uploaded_file))
        logger.debug('Working directory: {}'.format(working_dir))
        # Extract the archive into our temp dir
        logger.debug('Extracting archive {} to {}'.format(uploaded_file, working_dir))
        with tarfile.open(uploaded_file) as archive:
            archive.extractall(path=working_dir)
        # Upload to registry
        registry_target = '{}/{}:{}-{}'.format(registry, job_params['repository'], job_params['architecture'], job_params['revision'])
        logger.debug('Uploading {}/service.tar to registry {}'.format(working_dir, registry_target))
        subprocess.call(['skopeo', 'copy', '--dest-tls-verify=false', '--dest-no-creds',
                         'docker-archive:{}/service.tar'.format(working_dir),
                         'docker://{}'.format(registry_target)])
        return {}
