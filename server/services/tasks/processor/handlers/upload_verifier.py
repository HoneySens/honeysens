import configparser
from defusedxml import ElementTree
import distutils.util
import json
import os
import tarfile

from .. import constants
from .handler import HandlerInterface


class FileType:
    SERVICE_ARCHIVE = 0
    PLATFORM_ARCHIVE = 1


class UploadVerifier(HandlerInterface):

    logger = None

    def perform(self, logger, db, config_path, storage_path, working_dir, job_data):
        self.logger = logger
        job_params = json.loads(job_data['params'])
        config = configparser.ConfigParser()
        config.read_file(open(config_path))
        upload_path = '{}/{}'.format(constants.STORAGE_PATH, constants.UPLOAD_PATH)
        logger.debug('Performing job {}'.format(job_data['id']))
        uploaded_file = '{}/{}'.format(upload_path, job_params['path'])
        if not os.path.isfile(uploaded_file):
            raise Exception('Path {} does not exist or is not a file'.format(uploaded_file))
        logger.debug('Working directory: {}'.format(working_dir))
        result = {}
        try:
            with tarfile.open(uploaded_file) as archive:
                # Check archive content
                logger.debug('Checking that the archive contains service.tar and metadata.xml')
                content = archive.getnames()
                if not 'metadata.xml' in content:
                    raise Exception('Incomplete archive content')
                # Extraction
                logger.debug('Extracting metadata.xml to {}'.format(working_dir))
                archive.extract('metadata.xml', working_dir)
                # Parse metadata
                logger.debug('Parsing metadata.xml')
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
