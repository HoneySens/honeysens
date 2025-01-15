<?php
namespace HoneySens\app\models\constants;

enum EventClassification: int {
    case UNKNOWN = 0;
    case ICMP = 1;
    case CONN_ATTEMPT = 2;
    case LOW_HP = 3;
    case PORTSCAN = 4;
}
