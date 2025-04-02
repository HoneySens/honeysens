<?php
namespace HoneySens\app\models\constants;

enum SensorEAPOLMode: int {
    case DISABLED = 0;
    case MD5 = 1;
    case TLS = 2;
    case PEAP = 3;
    case TTLS = 4;
}
