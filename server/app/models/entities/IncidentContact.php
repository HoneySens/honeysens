<?php
namespace HoneySens\app\models\entities;

use HoneySens\app\models\entities\User;


/**
 * @Entity
 * @Table(name="contacts")
 */
class IncidentContact {

    const TYPE_MAIL = 0;
    const TYPE_USER = 1;

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * The E-Mail address to send messages to
     *
     * @Column(type="string", nullable=true)
     */
    protected $email;

    /**
     * The user that is acting as the contact for a particular division. This association is represented by this entity.
     * Messages will be sent to the E-Mail address that belongs to this user.
     *
     * @ManyToOne(targetEntity="HoneySens\app\models\entities\User", inversedBy="incidentContacts")
     */
    protected $user;

    /**
     * Whether to send weekly summaries to this contact
     *
     * @Column(type="boolean")
     */
    protected $sendWeeklySummary;

    /**
     * Whether to send instant critical event notifications to this contact
     *
     * @Column(type="boolean")
     */
    protected $sendCriticalEvents;

    /**
     * Whether to send notifications about ALL events to this contact
     *
     * @Column(type="boolean")
     */
    protected $sendAllEvents;

    /**
     * @ManyToOne(targetEntity="HoneySens\app\models\entities\Division", inversedBy="incidentContacts")
     */
    protected $division;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType() {
        if($this->email == null) return $this::TYPE_USER;
        else return $this::TYPE_MAIL;
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
        if($this->getType() == $this::TYPE_USER) {
            return $this->user->getEmail();
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
            'division' => $division
        );
    }
}