CMD_SOCKET = 'tcp://127.0.0.1:5555'
# Replace this with 'eth0' to expose the collector port to the outside (for development purposes)
DOCKER_BRIDGE = 'docker0'
DEFAULT_IFACE = 'eth0'
COLLECTOR_PORT = '5556'
CFG_SYMLINK = '/honeysens.cfg'
REVISION_MARKER = '/revision'  # File that marks the current revision
UPDATE_FW_DESTINATION = '/mnt/firmware'  # Where to store newly downloaded firmware
UPDATE_TAG_DESTINATION = '/mnt/tag'  # A file that contains the tag of the updated firmware


class Hooks:
    ON_INIT = 0  # cb()
    ON_POLL = 1  # cb(config_data)
    ON_APPLY_CONFIG = 2  # cb(config, reset_network)
    ON_BEFORE_POLL = 3  # cb(config, config_dir)
    ON_POLL_ERROR = 4  # cb()
    ON_EVENT = 5  # cb()
