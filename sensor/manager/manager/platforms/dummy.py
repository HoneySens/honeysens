from __future__ import absolute_import

import logging

from manager.platforms.generic import GenericPlatform


class Platform(GenericPlatform):
    def __init__(self, hook_mgr, interface, config_dir, config_archive):
        logging.getLogger(__name__).info('Initializing platform module: Dummy')
