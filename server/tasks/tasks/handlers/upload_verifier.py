#!/usr/bin/env python2

from defusedxml import ElementTree
import distutils.util
import json
import logging
import os
import tarfile

from tasks import constants
from tasks.handlers.handler import Handler


class FileType:
    SERVICE_ARCHIVE = 0
    PLATFORM_ARCHIVE = 1


class UploadVerifier(Handler):

    logger = None
    upload_path = None

    def __init__(self, config, storage_path):
        super(UploadVerifier, self).__init__(config, storage_path)
        self.logger = logging.getLogger(__name__)
        self.upload_path = '{}/{}'.format(config.get('server', 'data_path'), constants.UPLOAD_PATH)
        self.logger.info('Upload verifier initialized, expecting uploads in {}'.format(self.upload_path))

    def perform(self, db, working_dir, job_data):
        self.logger.debug('Performing job {}'.format(job_data['id']))
        job_params = json.loads(job_data['params'])
        uploaded_file = '{}/{}'.format(self.upload_path, job_params['path'])
        if not os.path.isfile(uploaded_file):
            raise Exception('Path {} does not exist or is not a file'.format(uploaded_file))
        self.logger.debug('Working directory: {}'.format(working_dir))
        result = {}
        try:
            with tarfile.open(uploaded_file) as archive:
                # Check archive content
                self.logger.debug('Checking that the archive contains service.tar and metadata.xml')
                content = archive.getnames()
                if not 'metadata.xml' in content:
                    raise Exception('Incomplete archive content')
                # Extraction
                self.logger.debug('Extracting metadata.xml to {}'.format(working_dir))
                archive.extract('metadata.xml', working_dir)
                # Parse metadata
                self.logger.debug('Parsing metadata.xml')
                xml = ElementTree.parse('{}/metadata.xml'.format(working_dir))
                # Identify the archive type and do delegate further inspection
                xml_root = xml.getroot().tag
                if xml_root == 'service':
                    result = self.inspect_service_archive(uploaded_file, content, xml, working_dir)
                elif xml_root == 'firmware':
                    result = self.inspect_platform_archive(uploaded_file, content, xml, working_dir)
                else:
                    raise Exception('Unknown archive type')
        except Exception as e:
            result = {'valid': False, 'reason': str(e)}
        return result

    def inspect_service_archive(self, path, archive_content, metadata, temp_path):
        self.logger.info('Verifying that {} is a valid service archive'.format(path))
        if not 'service.tar' in archive_content:
            raise Exception('Incomplete archive content')
        metadata_root = metadata.getroot()
        metadata_tags = [c.tag for c in metadata_root]
        self.logger.debug('Secure that all required XML tags are present')
        if not all(t in metadata_tags for t in ['name', 'architecture', 'rawNetworkAccess', 'catchAll',
                                                'portAssignment', 'repository', 'description', 'revision',
                                                'revisionDescription']):
            raise Exception('Invalid metadata: tags missing')
        self.logger.debug('Extracting metadata')
        return {'valid': True,
                'type': FileType.SERVICE_ARCHIVE,
                'name': metadata_root.find('name').text,
                'description': metadata_root.find('description').text,
                'repository': metadata_root.find('repository').text,
                'architecture': metadata_root.find('architecture').text,
                'revision': metadata_root.find('revision').text,
                'rawNetworkAccess': distutils.util.strtobool(metadata_root.find('rawNetworkAccess').text),
                'catchAll': distutils.util.strtobool(metadata_root.find('catchAll').text),
                'portAssignment': metadata_root.find('portAssignment').text,
                'revisionDescription': metadata_root.find('revisionDescription').text}

    def inspect_platform_archive(self, path, archive_content, metadata, temp_path):
        self.logger.info('Verifying that {} is a valid platform archive'.format(path))
        if not 'firmware.img' in archive_content:
            raise Exception('Incomplete archive content')
        metadata_root = metadata.getroot()
        metadata_tags = [c.tag for c in metadata_root]
        self.logger.debug('Secure that all required XML tags are present')
        if not all(t in metadata_tags for t in ['name', 'platform', 'version', 'description', 'changelog']):
            raise Exception('Invalid metadata: tags missing')
        self.logger.debug('Extracting metadata')
        return {'valid': True,
                'type': FileType.PLATFORM_ARCHIVE,
                'name': metadata_root.find('name').text,
                'platform': metadata_root.find('platform').text,
                'version': metadata_root.find('version').text,
                'description': metadata_root.find('description').text}
