<?php
namespace HoneySens\app\services\dto;

use HoneySens\app\models\constants\SensorStatusFlag;

class SensorStatus {
    // UNIX timestamp when this status reading was taken
    public int $timestamp;
    // General sensor state
    public SensorStatusFlag $status;
    // Sensor's primary IP address
    public string $ip;
    // Sensor's free RAM
    public int $freeMem;
    // Sensor's disk usage
    public int $diskUsage;
    // Sensor's total disk size
    public int $diskSize;
    // Sensor's current firmware revision
    public string $swVersion;
    // Status for each service scheduled to run on the sensor as array [$service_name => $service_status, ...]
    public array $serviceStatus;
}