#!/usr/bin/env python2

from __future__ import absolute_import

import argparse
import coloredlogs
import ConfigParser
import logging
import netifaces
import os
import Queue
import shutil
import signal
import sys
import tarfile
import tempfile
import time
import zmq

from . import collector
from . import commands
from . import config_updater
from . import event_processor
from . import hooks
from . import polling
from . import services
from . import state
from .platforms import dummy, bbb, docker
from .utils import constants

manager = None


class Manager:

    collector = None
    config_archive = None
    config = ConfigParser.ConfigParser()
    config_dir = None
    dev_mode = False
    event_processor = None
    event_queue = Queue.Queue()
    interface = None
    logger = None
    platform = None
    state_queue = Queue.Queue()
    state_worker = None
    zmq_context = zmq.Context()

    def __init__(self, config_archive, interface, platform, dev_mode, log_lvl):
        self.config_archive = config_archive
        self.dev_mode = dev_mode
        self.init_logging(log_lvl)
        self.init_config()
        self.init_interface(interface)
        self.platform = platform  # Temporarily save the name of the requested platform here, after start() the instance

    def init_logging(self, log_lvl):
        self.logger = logging.getLogger(__name__)
        coloredlogs.install(level=log_lvl.upper(), fmt='%(asctime)s [%(name)s] %(levelname)s %(message)s')
        self.logger.info('Starting up...')
        self.logger.info('Log level: {}'.format(log_lvl))

    def init_config(self):
        if not os.path.isfile(self.config_archive):
            self.logger.critical('Could not open configuration archive {}'.format(self.config_archive))
            exit()
        # Unpack and parse
        try:
            self.config_dir = tempfile.mkdtemp()
            with tarfile.open(self.config_archive) as config_archive:
                config_archive.extractall(self.config_dir)
            self.config.readfp(open('{}/honeysens.cfg'.format(self.config_dir)))
        except Exception as e:
            self.logger.critical('Could not parse configuration ({})'.format(str(e)))
            exit()
        # Create config symlink for 3rd parties
        if os.path.islink(constants.CFG_SYMLINK):
            os.remove(constants.CFG_SYMLINK)
        os.symlink('{}/honeysens.cfg'.format(self.config_dir), constants.CFG_SYMLINK)
        # Performing configuration update if required
        config_updater.update(self.config_archive, self.config_dir, self.config)
        self.logger.info('Configuration from {} initialized'.format(self.config_archive))

    def init_interface(self, interface):
        # Fall back to default interface in case none was provided
        if interface is None:
            self.interface = constants.DEFAULT_IFACE
        else:
            self.interface = interface
        # Ensure interface existence
        while not self.interface_available():
            time.sleep(1)
            self.logger.warning('Waiting for {} to become available...'.format(self.interface))
        self.logger.info('Sensor interface {} found'.format(self.interface))

    def init_platform(self):
        if self.platform == 'bbb':
            self.platform = bbb.Platform(hooks, self.interface, self.config_dir, self.config_archive)
        elif self.platform == 'docker':
            self.platform = docker.Platform(hooks, self.interface, self.config_dir, self.config_archive)
        else:
            self.platform = dummy.Platform(hooks, self.interface, self.config_dir, self.config_archive)

    def start(self):
        self.init_platform()
        services.init(self.config_dir, self.config, hooks, self.platform, self.interface)
        hooks.execute_hook(constants.Hooks.ON_INIT)
        # Apply initial configuration
        self.state_worker = state.StateWorker(self.state_queue)
        self.state_worker.start()
        self.logger.info('Applying initial configuration')
        self.state_worker.apply_config(self.config, {}, self.dev_mode is False)
        # Polling
        polling.start(self.config_dir, self.config, self.config_archive, self.interface, self.platform, self.state_queue)
        self.event_processor = event_processor.EventProcessor(self.event_queue, self.config, self.config_dir)
        self.event_processor.start()
        self.collector = collector.Collector(self.zmq_context, self.platform, self.event_queue, hooks)
        self.collector.start()
        self.logger.info('Startup sequence completed, launching command endpoint')
        commands.start(self.zmq_context, self)

    def shutdown(self):
        self.logger.info('Cleaning up')
        polling.stop()
        self.state_worker.stop()
        self.state_worker.join()
        self.collector.stop()
        self.collector.join()
        self.event_processor.stop()
        self.event_processor.join()
        services.cleanup()
        shutil.rmtree(self.config_dir)
        self.logger.info('Shutdown complete')

    def interface_available(self):
        return self.interface in netifaces.interfaces()


def sigterm_handler(signal, frame):
    manager.logger.warning('Received SIGTERM, performing graceful shutdown')
    manager.shutdown()
    sys.exit(0)


def main():
    global manager
    parser = argparse.ArgumentParser()
    parser.add_argument('config', help='Sensor configuration archive')
    parser.add_argument('-d', '--dev-mode', action='store_true', help='Development mode')
    parser.add_argument('-i', '--interface', help='Network interface to use')
    parser.add_argument('-l', '--log-level', choices=['debug', 'info', 'warning'], default='info', help='Logging level')
    parser.add_argument('-p', '--platform', help='Platform module')
    args = parser.parse_args()
    # Register signal handlers
    signal.signal(signal.SIGTERM, sigterm_handler)
    manager = Manager(args.config, args.interface, args.platform, args.dev_mode, args.log_level)
    manager.start()


if __name__ == '__main__':
    main()
