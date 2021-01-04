STORAGE_PATH = '/srv/data'  # Mount point of the HoneySens data directory
RESULT_PATH = 'tasks'  # Path relative to the global data path that is used to store task results
UPLOAD_PATH = 'upload'  # Path relative to the global data path that is used to store uploaded files


class TaskStatus:
    SCHEDULED = 0
    RUNNING = 1
    DONE = 2
    ERROR = 3


class TaskType:
    SENSORCFG_CREATOR = 0
    UPLOAD_VERIFIER = 1
    REGISTRY_MANAGER = 2
    EVENT_EXTRACTOR = 3
    EVENT_FORWARDER = 4
    SENSOR_TIMEOUT_CHECKER = 5
    EMAIL_EMITTER = 6
    WEEKLY_SUMMARIZER = 7
    CA_EXPIRATION_CHECKER = 8
    API_LOG_CLEANER = 9
