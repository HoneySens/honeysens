<?php
namespace HoneySens\app\models\entities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use HoneySens\app\models\constants\EventClassification;
use HoneySens\app\models\constants\EventService;
use HoneySens\app\models\constants\EventStatus;

#[Entity]
#[Table(name: "events")]
#[Index(columns: ["timestamp"], name: "timestamp_idx")]
class Event {

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    private int $id;

    /**
     * When this event took place. Events typically record network connections,
     * which might span a longer period of time with multiple packets.
     * Its up to the sensor software to set this value, which by default
     * will use the initial connection establishment as event timestamp.
     */
    #[Column(type: Types::DATETIME_MUTABLE)]
    public \DateTime $timestamp;

    /**
     * The sensor that reported this event.
     */
    #[ManyToOne(targetEntity: Sensor::class)]
    public Sensor $sensor;

    /**
     * The (type of) sensor service that generated this event.
     */
    #[Column()]
    public EventService $service;

    /**
     * The event type. Classification is done on the server side
     * and depends primarily on $service.
     */
    #[Column()]
    public EventClassification $classification;

    /**
     * Event source IP address. Denotes the address of the system
     * that caused this event after connecting to a sensor service.
     */
    #[Column(type: Types::STRING)]
    public string $source;

    /**
     * A one-liner to summarize the event. Format depends on
     * the service that created this event.
     */
    #[Column(type: Types::STRING)]
    public string $summary;

    /**
     * Determines the "workflow status" of this event and is a
     * support mechanism for users to flag events with status values
     * such as BUSY (currently under investigation) or RESOLVED.
     * New events arrive with status UNEDITED.
     */
    #[Column()]
    public EventStatus $status = EventStatus::UNEDITED;

    /**
     * Communication field to note custom metadata about this event,
     * such as investigation results or notes to other users/admins.
     */
    #[Column(type: Types::STRING, nullable: true)]
    public ?string $comment;

    /**
     * Timestamp that indicates the date and time of the last status or comment update.
     * If the comment hasn't been set by a person yet and the status hasn't been changed
     * once, this is null.
     */
    #[Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    public ?\DateTime $lastModificationTime;

    /**
     * A list of further event details. These are either a timestamped list
     * of interactions (login attempts, shell commands etc.) with a sensor service
     * or any other generic metadata logged by the sensor service,
     * but without a timestamp (such as the client user agent or similar connection data).
     */
    #[OneToMany(mappedBy: "event", targetEntity: EventDetail::class, cascade: ["remove"])]
    private Collection $details;

    /**
     * A list of timestamped network packets associated with this event.
     * The presence of these details depends on the service that generated the event.
     */
    #[OneToMany(mappedBy: "event", targetEntity: EventPacket::class, cascade: ["remove"])]
    private Collection $packets;

    public function __construct() {
        $this->details = new ArrayCollection();
        $this->packets = new ArrayCollection();
    }

    public function getId(): int {
        return $this->id;
    }

    /**
     * Adds interaction details or connection metadata to this event.
     */
    public function addDetails(EventDetail $details): void {
        $this->details[] = $details;
        $details->event = $this;
    }

    /**
     * Returns all details related to this event.
     */
    public function getDetails(): Collection {
        return $this->details;
    }

    /**
     * Adds details about a network packet that is associated with this event.
     */
    public function addPacket(EventPacket $packet): void {
        $this->packets[] = $packet;
        $packet->event = $this;
    }

    /**
     * Returns all network packets that are associated with this event.
     */
    public function getPackets(): Collection {
        return $this->packets;
    }

    public function getState(): array {
        return array(
            'id' => $this->id ?? null,
            'timestamp' => $this->timestamp->format('U'),
            'sensor' => $this->sensor->getId(),
            'division' => $this->sensor->division->getId(),
            'service' => $this->service->value,
            'classification' => $this->classification->value,
            'source' => $this->source,
            'summary' => $this->summary,
            'status' => $this->status,
            'comment' => $this->comment,
            'lastModificationTime' => $this->lastModificationTime?->format('U'),
            'numberOfPackets' => sizeof($this->getPackets()),
            'numberOfDetails' => sizeof($this->getDetails()),
            'archived' => false
        );
    }
}
