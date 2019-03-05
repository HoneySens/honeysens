#!/usr/bin/env python2

from __future__ import absolute_import

import argparse
import ConfigParser
import netifaces
import os
import shutil
import signal
import sys
import tarfile
import tempfile
import threading
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

    config_archive = None
    config = ConfigParser.ConfigParser()
    config_dir = None
    interface = None
    platform = None
    dev_mode = False
    zmq_context = zmq.Context()
    events = {}
    events_lock = threading.Lock()

    def __init__(self, config_archive, interface, platform, dev_mode):
        self.config_archive = config_archive
        self.init_config()
        self.init_interface(interface)
        self.platform = platform  # Temporarily save the name of the requested platform here, after start() the instance
        self.dev_mode = dev_mode

    def init_config(self):
        if not os.path.isfile(self.config_archive):
            print('manager: Error: Could not open configuration archive {}'.format(self.config_archive))
            exit()
        # Unpack and parse
        try:
            self.config_dir = tempfile.mkdtemp()
            with tarfile.open(self.config_archive) as config_archive:
                config_archive.extractall(self.config_dir)
            self.config.readfp(open('{}/honeysens.cfg'.format(self.config_dir)))
        except Exception as e:
            print('manager: Error: Could not parse configuration ({})'.format(str(e)))
        # Create config symlink for 3rd parties
        if os.path.islink(constants.CFG_SYMLINK):
            os.remove(constants.CFG_SYMLINK)
        os.symlink('{}/honeysens.cfg'.format(self.config_dir), constants.CFG_SYMLINK)
        # Performing configuration update if required
        config_updater.update(self.config_archive, self.config_dir, self.config)
        print('manager: Configuration from {} initialized'.format(self.config_archive))

    def init_interface(self, interface):
        # Fall back to default interface in case none was provided
        if interface is None:
            self.interface = constants.DEFAULT_IFACE
        else:
            self.interface = interface
        # Ensure interface existence
        print('PLATFORM: Waiting for {} to become available...'.format(self.interface))
        while not self.interface_available():
            time.sleep(1)

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
        print('manager: Applying initial configuration')
        state.apply_config(self.config, {}, self.dev_mode is False)
        # Polling
        polling.start(self.config_dir, self.config, self.config_archive, self.interface, self.platform)
        event_processor.start(self.config_dir, self.config, self.events, self.events_lock)
        collector.start(self.zmq_context, self.events, self.events_lock)
        print('===============================================================')
        print('Manager: Startup sequence completed, launching command endpoint')
        print('===============================================================')
        commands.start(self.zmq_context, self)

    def shutdown(self):
        print('manager: cleaning up')
        polling.stop()
        services.cleanup()
        shutil.rmtree(self.config_dir)
        print('manager: shutdown complete')

    def interface_available(self):
        return self.interface in netifaces.interfaces()


def sigterm_handler(signal, frame):
    print('manager: received SIGTERM, performing graceful shutdown')
    manager.shutdown()
    sys.exit(0)


def main():
    global manager
    parser = argparse.ArgumentParser()
    parser.add_argument('config', help='Sensor configuration archive')
    parser.add_argument('-i', '--interface', help='Network interface to use')
    parser.add_argument('-p', '--platform', help='Platform module')
    parser.add_argument('-d', '--dev-mode', action='store_true', help='Development mode')
    args = parser.parse_args()
    # Register signal handlers
    signal.signal(signal.SIGTERM, sigterm_handler)
    manager = Manager(args.config, args.interface, args.platform, args.dev_mode)
    manager.start()


if __name__ == '__main__':
    main()
