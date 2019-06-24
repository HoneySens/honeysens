#!/usr/bin/env python2

import json
import logging
import os
import subprocess
import tarfile

from tasks import constants
from tasks.handlers.handler import Handler


class RegistryManager(Handler):

    logger = None
    registry = None
    upload_path = None

    def __init__(self, config, storage_path):
        super(RegistryManager, self).__init__(config, storage_path)
        self.logger = logging.getLogger(__name__)
        self.registry = '{}:{}'.format(config.get('registry', 'host'), config.get('registry', 'port'))
        self.upload_path = '{}/{}'.format(config.get('server', 'data_path'), constants.UPLOAD_PATH)
        self.logger.info('Registry endpoint: {}'.format(self.registry))
        self.logger.info('Registry manager initialized, expecting uploads in {}'.format(self.upload_path))

    def perform(self, db, working_dir, job_data):
        self.logger.debug('Performing job {}'.format(job_data['id']))
        job_params = json.loads(job_data['params'])
        uploaded_file = '{}/{}'.format(self.upload_path, job_params['path'])
        if not os.path.isfile(uploaded_file):
            raise Exception('Path {} does not exist or is not a file'.format(uploaded_file))
        self.logger.debug('Working directory: {}'.format(working_dir))
        # Extract the archive into our temp dir
        self.logger.debug('Extracting archive {} to {}'.format(uploaded_file, working_dir))
        with tarfile.open(uploaded_file) as archive:
            archive.extractall(path=working_dir)
        # Upload to registry
        registry_target = '{}/{}:{}-{}'.format(self.registry, job_params['repository'], job_params['architecture'], job_params['revision'])
        self.logger.debug('Uploading {}/service.tar to registry {}'.format(working_dir, registry_target))
        subprocess.call(['/usr/bin/skopeo', 'copy', '--dest-tls-verify=false',
                 'docker-archive:{}/service.tar'.format(working_dir),
                 'docker://{}'.format(registry_target)])
        return {}