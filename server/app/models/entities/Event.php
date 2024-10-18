<?php
namespace HoneySens\app\models\entities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Index;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;
use HoneySens\app\models\constants\EventStatus;

#[Entity]
#[Table(name: "events")]
#[Index(name: "timestamp_idx", columns: ["timestamp"])]
class Event {

    const SERVICE_RECON = 0;
    const SERVICE_KIPPO = 1;
    const SERVICE_DIONAEA = 2;

    const CLASSIFICATION_UNKNOWN = 0;
    const CLASSIFICATION_ICMP = 1;
    const CLASSIFICATION_CONN_ATTEMPT = 2;
    const CLASSIFICATION_LOW_HP = 3;
    const CLASSIFICATION_PORTSCAN = 4;

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    protected $id;

    #[Column(type: Types::DATETIME_MUTABLE)]
    protected $timestamp;

    /**
     * The sensor this event was collected by
     */
    #[ManyToOne(targetEntity: Sensor::class)]
    protected $sensor;

    /**
     * The sensor service that generated this event
     */
    #[Column(type: Types::INTEGER)]
    protected $service;

    /**
     * Classification is done on the server side
     */
    #[Column(type: Types::INTEGER)]
    protected $classification;

    /**
     * Source IP address
     */
    #[Column(type: Types::STRING)]
    protected $source;

    /**
     * Most of the time a one-liner to summarize the event
     */
    #[Column(type: Types::STRING)]
    protected $summary;

    /**
     * Configurable display-only status
     */
    #[Column(type: Types::INTEGER)]
    protected $status = EventStatus::UNEDITED->value;

    /**
     * Comment for the guy who works on the event
     */
    #[Column(type: Types::STRING, nullable: true)]
    protected $comment;

    /**
     * Timestamp that indicates the date and time of the last status or comment update.
     */
    #[Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected $lastModificationTime;

    #[OneToMany(targetEntity: EventDetail::class, mappedBy: "event", cascade: ["remove"])]
    protected $details;

    /**
     * List of IP packets that belong to this event.
     */
    #[OneToMany(targetEntity: EventPacket::class, mappedBy: "event", cascade: ["remove"])]
    protected $packets;

    public function __construct() {
        $this->details = new ArrayCollection();
        $this->packets = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set timestamp
     *
     * @param \DateTime $timestamp
     * @return Event
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
     * Set sensor
     *
     * @param Sensor $sensor
     * @return Event
     */
    public function setSensor(Sensor $sensor = null) {
        $this->sensor = $sensor;
        return $this;
    }

    /**
     * Get sensor
     *
     * @return Sensor
     */
    public function getSensor() {
        return $this->sensor;
    }

    /**
     * Set service
     *
     * @param integer $service
     * @return Event
     */
    public function setService($service) {
        $this->service = $service;
        return $this;
    }

    /**
     * Get service
     *
     * @return integer
     */
    public function getService() {
        return $this->service;
    }

    /**
     * Set classification
     *
     * @param integer $classification
     * @return Event
     */
    public function setClassification($classification) {
        $this->classification = $classification;
        return $this;
    }

    /**
     * Get classification
     *
     * @return integer
     */
    public function getClassification() {
        return $this->classification;
    }

    /**
     * Set source
     *
     * @param string $source
     * @return Event
     */
    public function setSource($source) {
        $this->source = $source;
        return $this;
    }

    /**
     * Get source
     *
     * @return string
     */
    public function getSource() {
        return $this->source;
    }

    /**
     * Set summary
     *
     * @param string $summary
     * @return Event
     */
    public function setSummary($summary) {
        $this->summary = $summary;
        return $this;
    }

    /**
     * Get summary
     *
     * @return string
     */
    public function getSummary() {
        return $this->summary;
    }

    public function setStatus(EventStatus $status): Event {
        $this->status = $status->value;
        return $this;
    }

    public function getStatus(): int {
        return $this->status;
    }

    /**
     * Set Comment
     *
     * @param string $comment
     * @return Event
     */
    public function setComment($comment) {
        $this->comment = $comment;
        return $this;
    }

    /**
     * Get comment
     *
     * @return string
     */
    public function getComment() {
        return $this->comment;
    }

    /**
     * Updates the last modification time.
     *
     * @param \DateTime $lastModificationTime
     * @return Event
     */
    public function setLastModificationTime($lastModificationTime) {
        $this->lastModificationTime = $lastModificationTime;
        return $this;
    }

    /**
     * Returns the last modification timestamp.
     *
     * @return \DateTime
     */
    public function getLastModificationTime() {
        return $this->lastModificationTime;
    }

    /**
     * Add event details
     *
     * @param EventDetail $details
     * @return Event
     */
    public function addDetails(EventDetail $details) {
        $this->details[] = $details;
        $details->setEvent($this);
        return $this;
    }

    /**
     * Remove event details
     *
     * @param EventDetail $details
     * @return Event
     */
    public function removeDetails(EventDetail $details) {
        $this->details->removeElement($details);
        $details->setEvent(null);
        return $this;
    }

    /**
     * Returns all details related to this event
     *
     * @return ArrayCollection
     */
    public function getDetails() {
        return $this->details;
    }

    /**
     * Add related packet information
     *
     * @param EventPacket $packet
     * @return Event
     */
    public function addPacket(EventPacket $packet) {
        $this->packets[] = $packet;
        $packet->setEvent($this);
        return $this;
    }

    /**
     * Removes related packet information
     *
     * @param EventPacket $packet
     * @return Event
     */
    public function removePacket(EventPacket $packet) {
        $this->packets->removeElement($packet);
        $packet->setEvent(null);
        return $this;
    }

    /**
     * Returns all packets related to this event
     *
     * @return ArrayCollection
     */
    public function getPackets() {
        return $this->packets;
    }

    public function getState() {
        $sensor = $this->getSensor() == null ? '' : $this->getSensor()->getId();
        $division = $sensor == null ? null : $this->getSensor()->division->getId();
        $lastmod = $this->getLastModificationTime() ? $this->getLastModificationTime()->format('U') : null;
        return array(
            'id' => $this->getId(),
            'timestamp' => $this->getTimestamp()->format('U'),
            'sensor' => $sensor,
            'division' => $division,
            'service' => $this->getService(),
            'classification' => $this->getClassification(),
            'source' => $this->getSource(),
            'summary' => $this->getSummary(),
            'status' => $this->getStatus(),
            'comment' => $this->getComment(),
            'lastModificationTime' => $lastmod,
            'numberOfPackets' => sizeof($this->getPackets()),
            'numberOfDetails' => sizeof($this->getDetails()),
            'archived' => false
        );
    }
}
