<?php
namespace HoneySens\app\models\constants;

enum TransportProtocol: int {
    case UDP = 0;
    case TCP = 1;
}
