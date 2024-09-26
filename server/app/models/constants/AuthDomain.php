<?php
namespace HoneySens\app\models\constants;

enum AuthDomain: int {
    case LOCAL = 0;
    case LDAP = 1;
}
