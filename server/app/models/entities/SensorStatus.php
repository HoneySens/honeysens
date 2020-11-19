<?php
namespace HoneySens\app\models\entities;

use stdClass;

/**
 * @Entity
 * @Table(name="statuslogs")
 */
class SensorStatus {

    const STATUS_ERROR = 0;    // Sensor is running, but encountered an internal problem
    const STATUS_RUNNING = 1;  // Sensor is up and running
    const STATUS_UPDATING = 2; // Sensor is currently performing a firmware update
    const STATUS_TIMEOUT = 3;  // Sensor didn't send a status report for a certain amount of time

    const SERVICE_STATUS_RUNNING = 0;
    const SERVICE_STATUS_SCHEDULED = 1;
    const SERVICE_STATUS_ERROR = 2;

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="HoneySens\app\models\entities\Sensor", inversedBy="status")
     */
    protected $sensor;

    /**
     * @Column(type="datetime")
     */
    protected $timestamp;

    /**
     * @Column(type="integer")
     */
    protected $status;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $ip;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $freeMem;

    /**
     * Disk usage in Megabytes.
     *
     * @Column(type="integer", nullable=true)
     */
    protected $diskUsage;

    /**
     * Total disk size in Megabytes.
     *
     * @Column(type="integer", nullable=true)
     */
    protected $diskTotal;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $swVersion;

    /**
     * JSON-serialized stdClass object that stores service status data as
     * reported by the sensor: {service_name: service_status, ...}.
     *
     * @Column(type="string", nullable=true)
     */
    protected $serviceStatus;

    /**
     * Timestamp that depicts when this sensor originally went "online" (after its last disconnection).
     * This timestamp is copied over from one SensorStatus to the next as long as the sensor is online without interruption.
     *
     * @Column(type="datetime", nullable=true)
     */
    protected $runningSince;

    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set sensor
     *
     * @param \HoneySens\app\models\entities\Sensor $sensor
     * @return SensorStatus
     */
    public function setSensor(\HoneySens\app\models\entities\Sensor $sensor = null) {
        $this->sensor = $sensor;
        return $this;
    }

    /**
     * Get sensor
     *
     * @return \HoneySens\app\models\entities\Sensor 
     */
    public function getSensor() {
        return $this->sensor;
    }

    /**
     * Set timestamp
     *
     * @param \DateTime $timestamp
     * @return SensorStatus
     */
    public function setTimestamp(\DateTime $timestamp) {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * Get timestamp
     *
     * @return \DateTime 
     */
    public function getTimestamp() {
        return $this->timestamp;
    }
    
    /**
     * Set current status
     * 
     * @param integer $status
     * @return \HoneySens\app\models\entities\SensorStatus
     */
    public function setStatus($status) {
        $this->status = $status;
        return $this;
    }
    
    /**
     * Get current status
     * 
     * @return integer
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Set ip address
     *
     * @param string $ip
     * @return \HoneySens\app\models\entities\SensorStatus
     */
    public function setIP($ip) {
        $this->ip = $ip;
        return $this;
    }

    /**
     * Get ip address
     *
     * @return string
     */
    public function getIP() {
        return $this->ip;
    }

    /**
     * Set free memory in MB
     *
     * @param integer $freeMem
     * @return SensorStatus
     */
    public function setFreeMem($freeMem) {
        $this->freeMem = $freeMem;
        return $this;
    }

    /**
     * Get free memory in MB
     *
     * @return integer
     */
    public function getFreeMem() {
        return $this->freeMem;
    }

    /**
     * Set the current disk usage (MB).
     *
     * @param integer $usage
     * @return $this
     */
    public function setDiskUsage($usage) {
        $this->diskUsage = $usage;
        return $this;
    }

    /**
     * Get the current disk usage (MB).
     *
     * @return integer
     */
    public function getDiskUsage() {
        return $this->diskUsage;
    }

    /**
     * Set the total disk size (MB).
     *
     * @param integer $total
     * @return $this
     */
    public function setDiskTotal($total) {
        $this->diskTotal = $total;
        return $this;
    }

    /**
     * Get the total disk size (MB).
     *
     * @return integer
     */
    public function getDiskTotal() {
        return $this->diskTotal;
    }

    /**
     * Set sensor software version
     *
     * @param string $swVersion
     * @return SensorStatus
     */
    public function setSWVersion($swVersion) {
        $this->swVersion = $swVersion;
        return $this;
    }

    /**
     * Get sensor software version
     */
    public function getSWVersion() {
        return $this->swVersion;
    }

    /**
     * Sets the service status, expects an object with attributes {$service_name => $service_status, ...}.
     *
     * @param stdClass $serviceStatus
     * @return $this
     */
    public function setServiceStatus($serviceStatus) {
        $this->serviceStatus = json_encode($serviceStatus);
        return $this;
    }

    /**
     * Returns the service status as an object with attributes {$service_name => $service_status, ...}.
     *
     * @return stdClass
     */
    public function getServiceStatus() {
        return json_decode($this->serviceStatus);
    }

    /**
     * Set the timestamp that indicates since when this sensor is running.
     *
     * @param \DateTime $timestamp
     * @return SensorStatus
     */
    public function setRunningSince(\DateTime $timestamp) {
        $this->runningSince = $timestamp;
        return $this;
    }

    /**
     * Get timestamp that indicates since when this sensor is running.
     *
     * @return \DateTime
     */
    public function getRunningSince() {
        return $this->runningSince;
    }

    public function getState() {
        return array(
            'id' => $this->getId(),
            'sensor' => $this->getSensor()->getId(),
            'timestamp' => $this->getTimestamp()->format('U'),
            'status' => $this->getStatus(),
            'ip' => $this->getIP(),
            'free_mem' => $this->getFreeMem(),
            'disk_usage' => $this->getDiskUsage(),
            'disk_total' => $this->getDiskTotal(),
            'sw_version' => $this->getSWVersion(),
            'service_status' => $this->getServiceStatus(),
            'running_since' => $this->getRunningSince() ? $this->getRunningSince()->format('U') : null
        );
    }
}