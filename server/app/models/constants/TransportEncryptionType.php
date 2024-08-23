<?php
namespace HoneySens\app\models\constants;

enum TransportEncryptionType: int {
    case NONE = 0;
    case STARTTLS = 1;
    case TLS = 2;
}
