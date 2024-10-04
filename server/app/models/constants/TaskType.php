<?php
namespace HoneySens\app\models\constants;

enum TaskType: int {
    case SENSORCFG_CREATOR = 0;
    case UPLOAD_VERIFIER = 1;
    case REGISTRY_MANAGER = 2;
    case EVENT_EXTRACTOR = 3;
    case EVENT_FORWARDER = 4;
    case EMAIL_EMITTER = 6;
}
