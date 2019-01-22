<?php
namespace HoneySens\app\models\entities;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="divisions")
 */
class Division {

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @Column(type="string")
     */
    protected $name;

    /**
     * @OneToMany(targetEntity="HoneySens\app\models\entities\Sensor", mappedBy="division", cascade={"remove"})
     */
    protected $sensors;

    /**
     * @OneToMany(targetEntity="HoneySens\app\models\entities\IncidentContact", mappedBy="division", cascade={"remove"})
     */
    protected $incidentContacts;

    /**
     * @ManyToMany(targetEntity="User", mappedBy="divisions")
     */
    protected $users;

    /**
     * @OneToMany(targetEntity="HoneySens\app\models\entities\EventFilter", mappedBy="division", cascade={"remove"})
     */
    protected $eventFilters;

    public function __construct() {
        $this->sensors = new ArrayCollection();
        $this->incidentContacts = new ArrayCollection();
        $this->users = new ArrayCollection();
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
     * Set name
     *
     * @param string $name
     * @return Sensor
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Add a sensor to this division
     *
     * @param Sensor $sensor
     * @return $this
     */
    public function addSensor(Sensor $sensor) {
        $this->sensors[] = $sensor;
        $sensor->setDivision($this);
        return $this;
    }

    /**
     * Remove a sensor from this division
     *
     * @param Sensor $sensor
     * @return $this
     */
    public function removeSensor(Sensor $sensor) {
        $this->sensors->removeElement($sensor);
        $sensor->setDivision(null);
        return $this;
    }

    /**
     * Get all sensors associated with this division
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getSensors() {
        return $this->sensors;
    }

    /**
     * Add a contact to this division
     *
     * @param IncidentContact $contact
     * @return $this
     */
    public function addIncidentContact(IncidentContact $contact) {
        $this->incidentContacts[] = $contact;
        $contact->setDivision($this);
        return $this;
    }

    /**
     * Remove a contact from this division
     *
     * @param IncidentContact $contact
     * @return $this
     */
    public function removeIncidentContact(IncidentContact $contact) {
        $this->incidentContacts->removeElement($contact);
        $contact->setDivision(null);
        return $this;
    }

    /**
     * Get all incident contacts associated with this division
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getIncidentContacts() {
        return $this->incidentContacts;
    }

    /**
     * Add an user association
     *
     * @param User $user
     * @return $this
     */
    public function addUser(User $user) {
        $this->users[] = $user;
        return $this;
    }

    /**
     * Remove an user association
     *
     * @param User $user
     * @return $this
     */
    public function removeUser(User $user) {
        $this->users->removeElement($user);
        return $this;
    }

    /**
     * Get all users associated with this division
     *
     * @return \Doctrine\Common\Collections\ArrayCollection
     */
    public function getUsers() {
        return $this->users;
    }

    /**
     * Add an event filter
     *
     * @param EventFilter $filter
     * @return $this
     */
    public function addEventFilter(EventFilter $filter) {
        $this->eventFilters[] = $filter;
        return $this;
    }

    /**
     * Remove an event filter
     *
     * @param EventFilter $filter
     * @return $this
     */
    public function removeEventFilter(EventFilter $filter) {
        $this->eventFilters->removeElement($filter);
        return $this;
    }

    /**
     * Get all event filters associated with this division
     *
     * @return ArrayCollection
     */
    public function getEventFilters() {
        return $this->eventFilters;
    }

    public function getState() {
        $users = array();
        foreach($this->users as $user) {
            $users[] = $user->getId();
        }
        return array(
            'id' => $this->getId(),
            'name' => $this->getName(),
            'users' => $users
        );
    }
}