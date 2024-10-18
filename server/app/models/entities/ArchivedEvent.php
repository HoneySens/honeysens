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

/**
 * Archived read-only event.
 * Attributes match the one of Event, except for relationships: Those are replaced with static values.
 */
#[Entity]
#[Table(name: "archived_events")]
#[Index(name: "timestamp_idx", columns: ["timestamp"])]
class ArchivedEvent {

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    protected $id;

    /**
     * Original ID of this event.
     */
    #[Column(type: Types::INTEGER)]
    protected $oid;

    #[Column(type: Types::DATETIME_MUTABLE)]
    protected $timestamp;

    /**
     * Plaintext name of the sensor this event was collected by
     */
    #[Column(type: Types::STRING)]
    protected $sensor;

    /**
     * Division this event belongs to. In case the division doesn't exist anymore, this will be null.
     * In such cases, the $divisionName attribute will refer to the old plaintext division name.
     */
    #[ManyToOne(targetEntity: Division::class)]
    protected $division;

    /**
     * If this event isn't associated with a division anymore, this field holds the last known division name.
     */
    #[Column(type: Types::STRING, nullable: true)]
    protected $divisionName;

    #[Column(type: Types::INTEGER)]
    protected $service;

    #[Column(type: Types::INTEGER)]
    protected $classification;

    #[Column(type: Types::STRING)]
    protected $source;

    #[Column(type: Types::STRING)]
    protected $summary;

    #[Column(type: Types::INTEGER)]
    protected $status = 0;

    #[Column(type: Types::STRING, nullable: true)]
    protected $comment;

    #[Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected $lastModificationTime;

    /**
     * Timestamp of when this event was archived.
     */
    #[Column(type: Types::DATETIME_MUTABLE)]
    protected $archiveTime;

    /**
     * Holds event details as JSON structure.
     */
    #[Column(type: Types::TEXT)]
    protected $details;

    /**
     * Holds event packets as JSON structure.
     */
    #[Column(type: Types::TEXT)]
    protected $packets;

    /**
     * Creates a new archived event instance from the provided event's state.
     *
     * @param Event $e
     */
    public function __construct(Event $e) {
        $this->oid = $e->getId();
        $this->timestamp = $e->getTimestamp();
        $this->sensor = $e->getSensor()->name;
        $this->division = $e->getSensor()->division;
        $this->divisionName = null;
        $this->service = $e->getService();
        $this->classification = $e->getClassification();
        $this->source = $e->getSource();
        $this->summary = $e->getSummary();
        $this->status = $e->getStatus();
        $this->comment = $e->getComment();
        $this->lastModificationTime = $e->getLastModificationTime();
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
    public function getDetails() {
        return json_decode($this->details);
    }

    /**
     * Decodes and returns this event's packets as array.
     */
    public function getPackets() {
        return json_decode($this->packets);
    }

    public function getState() {
        $division = $this->division == null ? null : $this->division->getId();
        $lastmod = $this->lastModificationTime ? $this->lastModificationTime->format('U') : null;
        return array(
            'id' => $this->id,
            'oid' => $this->oid,
            'timestamp' => $this->timestamp->format('U'),
            'sensor' => $this->sensor,
            'division' => $division,
            'divisionName' => $this->divisionName,
            'service' => $this->service,
            'classification' => $this->classification,
            'source' => $this->source,
            'summary' => $this->summary,
            'status' => $this->status,
            'comment' => $this->comment,
            'lastModificationTime' => $lastmod,
            'archiveTime' => $this->archiveTime->format('U'),
            'numberOfPackets' => sizeof($this->getPackets()),
            'numberOfDetails' => sizeof($this->getDetails()),
            'archived' => true
        );
    }
}
