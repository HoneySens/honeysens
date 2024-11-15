<?php
namespace HoneySens\app\models\constants;

enum EventPacketProtocol: int {
    case UNKNOWN = 0;
    case TCP = 1;
    case UDP = 2;
}
