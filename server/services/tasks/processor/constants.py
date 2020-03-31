STORAGE_PATH = '/srv/data'  # Mount point of the HoneySens data directory
RESULT_PATH = 'tasks'  # Path relative to the global data path that is used to store task results
UPLOAD_PATH = 'upload'  # Path relatove to the global data path that is used to store uploaded files


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
