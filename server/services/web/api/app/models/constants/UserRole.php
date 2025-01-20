<?php
namespace HoneySens\app\models\constants;

enum UserRole: int {
    case GUEST = 0;
    case OBSERVER = 1;
    case MANAGER = 2;
    case ADMIN = 3;
}
