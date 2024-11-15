<?php
namespace HoneySens\app\models\entities;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use HoneySens\app\models\constants\EventDetailType;

/**
 * Detailed event data, optionally timestamped. Either used to
 * describe some interaction that happened as part of an event
 * at a certain point in time or any other generic logging data,
 * which is typically not timestamped (such as connection metadata).
 */
#[Entity]
#[Table(name: "event_details")]
class EventDetail {

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    private int $id;

    /**
     * The event this detailed data is associated with.
     */
    #[ManyToOne(targetEntity: Event::class, inversedBy: "details")]
    public Event $event;

    /**
     * An optional timestamp to track attacker-sensor interaction.
     */
    #[Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    public ?\DateTime $timestamp;

    /**
     * The type of this event detail data. Can be either INTERACTION
     * to represent a piece of data at a certain point in time or
     * GENERIC to represent any event-specific metadata.
     */
    #[Column()]
    public EventDetailType $type;

    /**
     * The actual raw data as string. Is set by the sensor service
     * that created the associated event.
     */
    #[Column(type: Types::STRING)]
    private string $data;

    public function getId(): int {
        return $this->id;
    }

    /**
     * Sets raw event data, which will be cut off
     * after 255 characters to prevent DoS via extremely
     * long payloads sent by attackers to sensor services.
     */
    public function setData(string $data): void {
        $this->data = substr($data, 0, 255);
    }

    /**
     * Returns the raw event data stored for this event.
     */
    public function getData(): string {
        return $this->data;
    }

    public function getState(): array {
        return array(
            'id' => $this->id ?? null,
            'timestamp' => $this->timestamp?->format('U'),
            'type' => $this->type->value,
            'data' => $this->data
        );
    }
}
