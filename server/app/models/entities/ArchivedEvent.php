<?php
namespace HoneySens\app\models\entities;
use Doctrine\ORM\Mapping as ORM;

/**
 * Archived read-only event.
 * Attributes match the one of Event, except for relationships: Those are replaced with static values.
 *
 * @ORM\Entity
 * @ORM\Table(name="archived_events",indexes={@ORM\Index(name="timestamp_idx", columns={"timestamp"})})
 */
class ArchivedEvent {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * Original ID of this event.
     *
     * @ORM\Column(type="integer")
     */
    protected $oid;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $timestamp;

    /**
     * Plaintext name of the sensor this event was collected by
     *
     * @ORM\Column(type="string")
     */
    protected $sensor;

    /**
     * Division this event belongs to. In case the division doesn't exist anymore, this will be null.
     * In such cases, the $divisionName attribute will refer to the old plaintext division name.
     *
     * @ORM\ManyToOne(targetEntity="HoneySens\app\models\entities\Division")
     */
    protected $division;

    /**
     * If this event isn't associated with a division anymore, this field holds the last known division name.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $divisionName;

    /**
     * @ORM\Column(type="integer")
     */
    protected $service;

    /**
     * @ORM\Column(type="integer")
     */
    protected $classification;

    /**
     * @ORM\Column(type="string")
     */
    protected $source;

    /**
     * @ORM\Column(type="string")
     */
    protected $summary;

    /**
     * @ORM\Column(type="integer")
     */
    protected $status = 0;

    /**
     * @ORM\Column(type="string", nullable=true)
     */
    protected $comment;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $lastModificationTime;

    /**
     * Timestamp of when this event was archived.
     *
     * @ORM\Column(type="datetime")
     */
    protected $archiveTime;

    /**
     * Holds event details as JSON structure.
     *
     * @ORM\Column(type="text")
     */
    protected $details;

    /**
     * Holds event packets as JSON structure.
     *
     * @ORM\Column(type="text")
     */
    protected $packets;

    /**
     * Creates a new archived event instance from the provided event's state.
     *
     * @param Event $e
     */
    public function __construct(Event $e) {
        $this->oid = $e->getId();
        $this->timestamp = $e->getTimestamp();
        $this->sensor = $e->getSensor()->getName();
        $this->division = $e->getSensor()->getDivision();
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
