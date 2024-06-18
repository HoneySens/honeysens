<?php
namespace HoneySens\app\models\constants;

enum EventFilterConditionField: int {
    case CLASSIFICATION = 0; // Event::$classification
    case SOURCE = 1;
    case TARGET = 2;
    case PROTOCOL = 3; // EventPacket::$protocol
}
