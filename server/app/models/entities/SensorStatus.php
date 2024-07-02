<?php
namespace HoneySens\app\models\entities;
use Doctrine\ORM\Mapping as ORM;
use HoneySens\app\models\constants\SensorStatusFlag;
use stdClass;

/**
 * @ORM\Entity
 * @ORM\Table(name="statuslogs")
 */
class SensorStatus {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="HoneySens\app\models\entities\Sensor", inversedBy="status")
     */
    protected $sensor;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $timestamp;

    /**
     * @ORM\Column(type="integer")
     */
    protected $status;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $ip;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $freeMem;

    /**
     * Disk usage in Megabytes.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $diskUsage;

    /**
     * Total disk size in Megabytes.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $diskTotal;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $swVersion;

    /**
     * JSON-serialized associative array that stores service status data as
     * reported by the sensor: {service_name: service_status, ...}.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $serviceStatus;

    /**
     * Timestamp that depicts when this sensor originally went "online" (after its last disconnection).
     * This timestamp is copied over from one SensorStatus to the next as long as the sensor is online without interruption.
     *
     * @ORM\Column(type="datetime", nullable=true)
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
     */
    public function setStatus(SensorStatusFlag $status): SensorStatus {
        $this->status = $status->value;
        return $this;
    }
    
    /**
     * Get current status
     */
    public function getStatus(): SensorStatusFlag {
        return SensorStatusFlag::from($this->status);
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
     * @param array $serviceStatus
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
        return json_decode($this->serviceStatus, true);
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
            'status' => $this->getStatus()->value,
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