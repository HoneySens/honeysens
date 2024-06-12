<?php
namespace HoneySens\app\models\constants;

enum EventStatus: int {
    case UNEDITED = 0;
    case BUSY = 1;
    case RESOLVED = 2;
    case IGNORED = 3;
}