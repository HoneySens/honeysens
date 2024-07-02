<?php
namespace HoneySens\app\models\constants;

/**
 * Depicts the status of a single service running on a particular sensor.
 */
enum SensorServiceStatus: int {
    case RUNNING = 0;    // Service is running
    case SCHEDULED = 1;  // Service is scheduled and will be loaded during the next polling process
    case ERROR = 2;      // Service startup failed
}