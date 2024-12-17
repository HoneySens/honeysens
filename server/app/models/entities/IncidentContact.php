<?php
namespace HoneySens\app\models\entities;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use HoneySens\app\models\constants\ContactType;

/**
 * Division-specific contact to send e-mail notifications to.
 * A contact is either specified as a user reference, using the
 * user's e-mail address, or an independent e-mail address.
 * In addition, an IncidentContact specifies the type of notifications to send.
 */
#[Entity]
#[Table(name: "contacts")]
class IncidentContact {

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    private int $id;

    /**
     * An e-mail address to send notifications to. If this is null,
     * notifications will be sent to $user instead.
     */
    #[Column(type: Types::STRING, nullable: true)]
    public ?string $email;

    /**
     * The user that is acting as the contact for a particular division.
     * This association is represented by this entity. Messages will be sent
     * to the E-Mail address that belongs to this user.
     */
    #[ManyToOne(targetEntity: User::class, inversedBy: "incidentContacts")]
    public ?User $user;


    /**
     * Whether to send weekly summaries to this incident contact.
     */
    #[Column(type: Types::BOOLEAN)]
    public bool $sendWeeklySummary = false;

    /**
     * Whether to send instant critical event notifications to this incident contact.
     */
    #[Column(type: Types::BOOLEAN)]
    public bool $sendCriticalEvents = false;

    /**
     * Whether to send notifications about ALL events to this incident contact.
     */
    #[Column(type: Types::BOOLEAN)]
    public bool $sendAllEvents = false;

    /**
     * Whether to send a notification whenever a sensor in this division exceeds its timeout interval.
     */
    #[Column(type: Types::BOOLEAN)]
    public bool $sendSensorTimeouts = false;

    #[ManyToOne(targetEntity: Division::class, inversedBy: "incidentContacts")]
    public Division $division;

    public function getId(): int {
        return $this->id;
    }

    /**
     * Determines the type of this IncidentContact,
     * which is either $email (if set) or the e-mail
     * address of the associated $user.
     */
    public function getType(): ContactType {
        if($this->email === null) return ContactType::USER;
        else return ContactType::EMAIL;
    }

    /**
     * Returns the e-mail address of this incident contact,
     * taken either from $email or $user.
     */
    public function getEMail(): ?string {
        if($this->getType() === ContactType::USER) {
            return $this->user->email;
        } else return $this->email;
    }

    public function getState(): array {
        return array(
            'id' => $this->id ?? null,
            'type' => $this->getType(),
            'email' => $this->email,
            'user' => $this->user?->getId(),
            'sendWeeklySummary' => $this->sendWeeklySummary,
            'sendCriticalEvents' => $this->sendCriticalEvents,
            'sendAllEvents' => $this->sendAllEvents,
            'sendSensorTimeouts' => $this->sendSensorTimeouts,
            'division' => $this->division->getId()
        );
    }
}
