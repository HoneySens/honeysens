<?php
namespace HoneySens\app\models\entities;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use HoneySens\app\models\constants\EventClassification;
use HoneySens\app\models\constants\EventService;
use HoneySens\app\models\constants\EventStatus;

/**
 * Archived read-only event.
 * Attributes match those of Event, except for relationships: They are replaced with static values.
 */
#[Entity]
#[Table(name: "archived_events")]
#[Index(columns: ["timestamp"], name: "timestamp_idx")]
class ArchivedEvent {

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    private int $id;

    /**
     * Original ID of this event.
     */
    #[Column(type: Types::INTEGER)]
    public int $oid;

    #[Column(type: Types::DATETIME_MUTABLE)]
    public \DateTime $timestamp;

    /**
     * Plaintext name of the sensor this event was collected by.
     */
    #[Column(type: Types::STRING)]
    public string $sensor;

    /**
     * Division this event belongs to. In case the division doesn't exist anymore, this will be null.
     * In such cases, the $divisionName attribute will refer to the previous plaintext division name.
     */
    #[ManyToOne(targetEntity: Division::class)]
    public ?Division $division;

    /**
     * If this event isn't associated with a division anymore, this field holds the last known division name.
     */
    #[Column(type: Types::STRING, nullable: true)]
    public ?string $divisionName;

    #[Column()]
    public EventService $service;

    #[Column()]
    public EventClassification $classification;

    #[Column(type: Types::STRING)]
    public string $source;

    #[Column(type: Types::STRING)]
    public string $summary;

    #[Column()]
    public EventStatus $status = EventStatus::UNEDITED;

    #[Column(type: Types::STRING, nullable: true)]
    public ?string $comment;

    #[Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    public ?\DateTime $lastModificationTime;

    /**
     * Timestamp of when this event was archived.
     */
    #[Column(type: Types::DATETIME_MUTABLE)]
    public \DateTime $archiveTime;

    /**
     * Holds event details as a base64-encoded JSON array.
     */
    #[Column(type: Types::TEXT)]
    private string $details;

    /**
     * Holds event packets as base64-encoded JSON array.
     */
    #[Column(type: Types::TEXT)]
    private string $packets;

    /**
     * Creates a new archived event instance from the provided event's state.
     *
     * @param Event $e
     */
    public function __construct(Event $e) {
        $this->oid = $e->getId();
        $this->timestamp = $e->timestamp;
        $this->sensor = $e->sensor->name;
        $this->division = $e->sensor->division;
        $this->divisionName = null;
        $this->service = $e->service;
        $this->classification = $e->classification;
        $this->source = $e->source;
        $this->summary = $e->summary;
        $this->status = $e->status;
        $this->comment = $e->comment;
        $this->lastModificationTime = $e->lastModificationTime;
        $this->archiveTime = new \DateTime();
        $details = array();
        $packets = array();
        foreach($e->getDetails() as $detail) $details[] = $detail->getState();
        foreach($e->getPackets() as $packet) $packets[] = $packet->getState();
        $this->details = json_encode($details);
        $this->packets = json_encode($packets);
    }

    /**
     * Decodes and returns this event's details as array.
     */
    public function getDetails(): array {
        return json_decode($this->details);
    }

    /**
     * Decodes and returns this event's packets as array.
     */
    public function getPackets(): array {
        return json_decode($this->packets);
    }

    public function getState(): array {
        return array(
            'id' => $this->id ?? null,
            'oid' => $this->oid,
            'timestamp' => $this->timestamp->format('U'),
            'sensor' => $this->sensor,
            'division' => $this->division?->getId(),
            'divisionName' => $this->divisionName,
            'service' => $this->service,
            'classification' => $this->classification,
            'source' => $this->source,
            'summary' => $this->summary,
            'status' => $this->status,
            'comment' => $this->comment,
            'lastModificationTime' => $this->lastModificationTime?->format('U'),
            'archiveTime' => $this->archiveTime->format('U'),
            'numberOfPackets' => sizeof($this->getPackets()),
            'numberOfDetails' => sizeof($this->getDetails()),
            'archived' => true
        );
    }
}
