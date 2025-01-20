<?php
namespace HoneySens\app\models\constants;

enum EventFilterConditionType: int {
    case SOURCE_VALUE = 0;
    case SOURCE_REGEX = 1;
    case SOURCE_IPRANGE = 2;
    case TARGET_PORT = 3;
}