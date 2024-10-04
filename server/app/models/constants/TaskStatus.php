<?php
namespace HoneySens\app\models\constants;

enum TaskStatus: int {
    case SCHEDULED = 0;
    case RUNNING = 1;
    case DONE = 2;
    case ERROR = 3;
}
