<?php
namespace HoneySens\app\models\entities;
use Doctrine\ORM\Mapping as ORM;

/**
 * Class LogEntry
 *
 * Represents a single log entry that records an action made by a user regarding a REST resource.
 * Due to this being just a log entry, we don't keep direct references in this class, bus instead save IDs of
 * associated entities directly.
 *
 * @ORM\Entity
 * @ORM\Table(name="logs")
 * @package HoneySens\app\models\entities
 */
class LogEntry {

    const RESOURCE_GENERIC = 0;
    const RESOURCE_CONTACTS = 1;
    const RESOURCE_DIVISIONS = 2;
    const RESOURCE_EVENTFILTERS = 3;
    const RESOURCE_EVENTS = 4;
    const RESOURCE_PLATFORMS = 5;
    const RESOURCE_SENSORS= 6;
    const RESOURCE_SERVICES = 7;
    const RESOURCE_SETTINGS = 8;
    const RESOURCE_TASKS = 9;
    const RESOURCE_USERS = 10;
    const RESOURCE_SYSTEM = 11;
    const RESOURCE_SESSIONS = 12;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $timestamp;

    /**
     * ID of the user that performed this action.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $userID;

    /**
     * ID of the resource that was subject to this action.
     * If a resource with this ID doesn't exist anymore, this log entry references historic data.
     *
     * @ORM\Column(type="integer", nullable=true)
     */
    protected $resourceID;

    /**
     * The type of resource that was subject to this action.
     *
     * @ORM\Column(type="integer")
     */
    protected $resourceType = self::RESOURCE_GENERIC;

    /**
     * The actual log message, e.g. what happened.
     *
     * @ORM\Column(type="string")
     */
    protected $message;

    /**
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set timestamp
     *
     * @param \DateTime $timestamp
     * @return LogEntry
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
     * Sets the user ID this log entry can be attributed to.
     *
     * @param int|null $userID
     * @return LogEntry
     */
    public function setUserID($userID = null) {
        $this->userID = $userID;
        return $this;
    }

    /**
     * Returns the user ID this log entry can be attributed to.
     *
     * @return int|null
     */
    public function getUserID() {
        return $this->userID;
    }

    /**
     * Sets the resource ID this log entry can be attributed to.
     *
     * @param int|null $resourceID
     * @return LogEntry
     */
    public function setResourceID($resourceID = null) {
        $this->resourceID = $resourceID;
        return $this;
    }

    /**
     * Returns the resource ID this log entry can be attributed to.
     *
     * @return int|null
     */
    public function getResourceID() {
        return $this->resourceID;
    }

    /**
     * Sets the resource type this log entry can be attributed to.
     *
     * @param int $resourceType
     * @return LogEntry
     */
    public function setResourceType($resourceType = null) {
        $this->resourceType = $resourceType;
        return $this;
    }

    /**
     * Returns the resource type this log entry can be attributed to.
     *
     * @return int
     */
    public function getResourceType() {
        return $this->resourceType;
    }

    /**
     * Sets the message content of this log entry.
     *
     * @param string $message
     * @return LogEntry
     */
    public function setMessage($message) {
        $this->message = $message;
        return $this;
    }

    /**
     * Returns the message content of this log entry.
     *
     * @return string
     */
    public function getMessage() {
        return $this->message;
    }

    public function getState() {
        return array(
            'id' => $this->getId(),
            'timestamp' => $this->getTimestamp()->format('U'),
            'user_id' => $this->getUserID(),
            'resource_id' => $this->getResourceID(),
            'resource_type' => $this->getResourceType(),
            'message' => $this->getMessage()
        );
    }
}