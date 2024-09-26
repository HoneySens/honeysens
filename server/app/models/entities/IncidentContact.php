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

#[Entity]
#[Table(name: "contacts")]
class IncidentContact {

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    protected $id;

    /**
     * The E-Mail address to send messages to
     */
    #[Column(type: Types::STRING, nullable: true)]
    protected $email;

    /**
     * The user that is acting as the contact for a particular division. This association is represented by this entity.
     * Messages will be sent to the E-Mail address that belongs to this user.
     */
    #[ManyToOne(targetEntity: User::class, inversedBy: "incidentContacts")]
    protected $user;

    /**
     * Whether to send weekly summaries to this contact
     */
    #[Column(type: Types::BOOLEAN)]
    protected $sendWeeklySummary;

    /**
     * Whether to send instant critical event notifications to this contact
     */
    #[Column(type: Types::BOOLEAN)]
    protected $sendCriticalEvents;

    /**
     * Whether to send notifications about ALL events to this contact
     */
    #[Column(type: Types::BOOLEAN)]
    protected $sendAllEvents;

    /**
     * Whether to send a notification whenever a sensor in this division exceeds its timeout interval.
     */
    #[Column(type: Types::BOOLEAN)]
    protected $sendSensorTimeouts;

    #[ManyToOne(targetEntity: Division::class, inversedBy: "incidentContacts")]
    protected $division;

    public function getId(): int {
        return $this->id;
    }

    /**
     * Get type
     *
     * @return ContactType
     */
    public function getType() {
        if($this->email === null) return ContactType::USER;
        else return ContactType::EMAIL;
    }

    /**
     * @param string $email
     * @return IncidentContact
     */
    public function setEMail($email) {
        $this->email = $email;
        return $this;
    }

    /**
     * @return string
     */
    public function getEMail() {
        if($this->getType() === ContactType::USER) {
            return $this->user->email;
        } else return $this->email;
    }

    /**
     * Set user
     *
     * @param User $user
     * @return $this
     */
    public function setUser(User $user = null) {
        $this->user = $user;
        return $this;
    }

    /**
     * Get user
     *
     * @return User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * @param boolean $sendSummary
     * @return IncidentContact
     */
    public function setSendWeeklySummary($sendSummary) {
        $this->sendWeeklySummary = $sendSummary;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getSendWeeklySummary() {
        return $this->sendWeeklySummary;
    }

    /**
     * @param boolean $sendCritical
     * @return IncidentContact
     */
    public function setSendCriticalEvents($sendCritical) {
        $this->sendCriticalEvents = $sendCritical;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getSendCriticalEvents() {
        return $this->sendCriticalEvents;
    }

    /**
     * @param boolean $sendAll
     * @return IncidentContact
     */
    public function setSendAllEvents($sendAll) {
        $this->sendAllEvents = $sendAll;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getSendAllEvents() {
        return $this->sendAllEvents;
    }

    /**
     * @param boolean $sendSensorTimeouts
     * @return IncidentContact
     */
    public function setSendSensorTimeouts($sendSensorTimeouts) {
        $this->sendSensorTimeouts = $sendSensorTimeouts;
        return $this;
    }

    /**
     * @return boolean
     */
    public function getSendSensorTimeouts() {
        return $this->sendSensorTimeouts;
    }


    /**
     * Set division
     *
     * @param Division $division
     * @return $this
     */
    public function setDivision(Division $division = null) {
        $this->division = $division;
        return $this;
    }

    /**
     * Get division
     *
     * @return mixed
     */
    public function getDivision() {
        return $this->division;
    }

    public function getState() {
        $division = $this->getDivision() == null ? null : $this->getDivision()->getId();
        $user = $this->getUser() == null ? null : $this->getUser()->getId();
        return array(
            'id' => $this->getId(),
            'type' => $this->getType(),
            'email' => $this->getEMail(),
            'user' => $user,
            'sendWeeklySummary' => $this->getSendWeeklySummary(),
            'sendCriticalEvents' => $this->getSendCriticalEvents(),
            'sendAllEvents' => $this->getSendAllEvents(),
            'sendSensorTimeouts' => $this->getSendSensorTimeouts(),
            'division' => $division
        );
    }
}
