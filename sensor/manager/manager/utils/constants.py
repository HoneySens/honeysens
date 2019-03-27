CMD_SOCKET = 'tcp://127.0.0.1:5555'
DEFAULT_IFACE = 'eth0'
COLLECTOR_PORT = '5556'
CFG_SYMLINK = '/honeysens.cfg'
REVISION_MARKER = '/revision'  # File that marks the current revision


class Hooks:
    ON_INIT = 0  # cb()
    ON_POLL = 1  # cb(config_data)
    ON_APPLY_CONFIG = 2  # cb(config, reset_network)
    ON_BEFORE_POLL = 3  # cb(config, config_dir)
    ON_POLL_ERROR = 4  # cb()
    ON_EVENT = 5  # cb()
    ON_SERVICE_NETWORK_CHANGE = 6  # cd()


class SensorStatus:
    ERROR = 0  # Currently unused
    RUNNING = 1
    UPDATING = 2


class ServiceStatus:
    RUNNING = 0
    SCHEDULED = 1
    ERROR = 2
