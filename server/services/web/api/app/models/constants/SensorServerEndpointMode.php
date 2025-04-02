<?php
namespace HoneySens\app\models\constants;

enum SensorServerEndpointMode: int {
    case DEFAULT = 0;  // Use globally configured server endpoint
    case CUSTOM = 1;  // Use a custom sensor-specific server endpoint
}