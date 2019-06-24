from __future__ import absolute_import

import logging

class Handler(object):

    config = None
    logger = None
    storage_path = None

    def __init__(self, config, storage_path):
        self.config = config
        self.logger = logging.getLogger(__name__)
        self.storage_path = storage_path

    def perform(self, db, working_dir, job_data):
        """Has to be overwritten by actual handlers. Performs the handler task and returns a dict with results."""
        pass