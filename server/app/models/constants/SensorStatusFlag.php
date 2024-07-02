<?php
namespace HoneySens\app\models\constants;

enum SensorStatusFlag: int {
    case ERROR = 0;    // Sensor is running, but encountered an internal problem
    case RUNNING = 1;  // Sensor is up and running
    case UPDATING = 2; // Sensor is currently performing a firmware update
    case TIMEOUT = 3;  // Sensor didn't send a status report for a certain amount of time
}