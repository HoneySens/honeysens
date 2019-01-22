from __future__ import absolute_import

from manager.platforms.generic import GenericPlatform


class Platform(GenericPlatform):
    def __init__(self, hook_mgr, interface, config_dir, config_archive):
        print('Initializing platform module: Dummy')
