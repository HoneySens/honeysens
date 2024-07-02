<?php
namespace HoneySens\app\models\constants;

enum SensorNetworkIPMode: int {
    case DHCP = 0;
    case STATIC = 1;
    case NONE = 2;  // Leave network interface unconfigured (used for virtual host-configured sensor interfaces)
}