<?php
namespace HoneySens\app\models\entities;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use HoneySens\app\models\constants\SensorStatusFlag;
use stdClass;

/**
 * Single sensor status log entry with a snapshot of
 * the system state at a particular point in time.
 */
#[Entity]
#[Table(name: "statuslogs")]
class SensorStatus {

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    private int $id;

    /**
     * The sensor that reported this status snapshot.
     */
    #[ManyToOne(targetEntity: Sensor::class, inversedBy: "status")]
    public Sensor $sensor;

    /**
     * When this status snapshot was taken.
     */
    #[Column(type: Types::DATETIME_MUTABLE)]
    public \DateTime $timestamp;

    /**
     * Current sensor state, a self assessment as reported by the sensor.
     */
    #[Column()]
    public SensorStatusFlag $status;

    /**
     * Primary sensor IP address.
     */
    #[Column(type: Types::STRING, nullable: true)]
    public ?string $ip;

    /**
     * Available RAM in Megabytes.
     */
    #[Column(type: Types::INTEGER, nullable: true)]
    public ?int $freeMem;

    /**
     * Disk usage in Megabytes.
     */
    #[Column(type: Types::INTEGER, nullable: true)]
    public ?int $diskUsage;

    /**
     * Total disk size in Megabytes.
     */
    #[Column(type: Types::INTEGER, nullable: true)]
    public ?int $diskTotal;

    #[Column(type: Types::STRING, nullable: true)]
    public ?string $swVersion;

    /**
     * JSON-serialized associative array that stores service status data as
     * reported by the sensor: {§service_name => $service_status, ...}.
     */
    #[Column(type: Types::STRING, nullable: true)]
    private ?string $serviceStatus;

    /**
     * Timestamp that depicts when this sensor originally went "online" (after its last disconnection).
     * This timestamp is copied over from one SensorStatus to the next as long as the sensor is online without interruption.
     */
    #[Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    public ?\DateTime $runningSince;

    public function getId(): int {
        return $this->id;
    }

    /**
     * Sets the service status, expects an object with attributes {$service_name => $service_status, ...}.
     */
    public function setServiceStatus(array $serviceStatus): void {
        $this->serviceStatus = json_encode($serviceStatus);
    }

    /**
     * Returns the service status as an object with attributes {$service_name => $service_status, ...}.
     */
    public function getServiceStatus(): ?array {
        return json_decode($this->serviceStatus, true);
    }

    public function getState(): array {
        return array(
            'id' => $this->id ?? null,
            'sensor' => $this->sensor->getId(),
            'timestamp' => $this->timestamp->format('U'),
            'status' => $this->status->value,
            'ip' => $this->ip,
            'free_mem' => $this->freeMem,
            'disk_usage' => $this->diskUsage,
            'disk_total' => $this->diskTotal,
            'sw_version' => $this->swVersion,
            'service_status' => $this->getServiceStatus(),
            'running_since' => $this->runningSince?->format('U'),
        );
    }
}
