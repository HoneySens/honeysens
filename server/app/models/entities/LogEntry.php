<?php
namespace HoneySens\app\models\entities;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use HoneySens\app\models\constants\LogResource;

/**
 * Represents a single log entry that records an action made by a user regarding a REST resource.
 * Due to this being just a log entry, we don't keep direct references in this class, bus instead save IDs of
 * associated entities directly.
 */
#[Entity]
#[Table(name: "logs")]
class LogEntry {

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    private int $id;

    #[Column(type: Types::DATETIME_MUTABLE)]
    public \DateTime $timestamp;

    /**
     * ID of the user that performed this action.
     */
    #[Column(type: Types::INTEGER, nullable: true)]
    public ?int $userID;

    /**
     * ID of the resource that was subject of this action.
     * If a resource with this ID doesn't exist anymore, this log entry references historic data.
     */
    #[Column(type: Types::INTEGER, nullable: true)]
    public ?int $resourceID;

    /**
     * The type of resource that was subject of this action.
     */
    #[Column()]
    public LogResource $resourceType = LogResource::GENERIC;

    /**
     * The actual log message, e.g. what happened.
     */
    #[Column(type: Types::STRING)]
    public string $message;

    public function getId(): int {
        return $this->id;
    }

    public function getState() {
        return array(
            'id' => $this->getId(),
            'timestamp' => $this->timestamp->format('U'),
            'user_id' => $this->userID,
            'resource_id' => $this->resourceID,
            'resource_type' => $this->resourceType->value,
            'message' => $this->message
        );
    }
}
