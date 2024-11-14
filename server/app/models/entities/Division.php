<?php
namespace HoneySens\app\models\entities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

/**
 * Basic building block for multitenancy. All users, sensors, incident contacts,
 * events and filters (and their downstream entities) are associated with exactly one division.
 * Users can only interact with entities of their own divisions.
 */
#[Entity]
#[Table(name: "divisions")]
class Division {

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    private int $id;

    /**
     * Short division name.
     */
    #[Column(type: Types::STRING)]
    public string $name;

    /**
     * Sensors associated with this division. Each sensor
     * can only be associated with a single division.
     */
    #[OneToMany(mappedBy: "division", targetEntity: Sensor::class)]
    private Collection $sensors;

    /**
     * Incident contacts association with this division.
     * Each contact can only be associated with a single division.
     * They receive e-mail notifications upon various system events.
     */
    #[OneToMany(mappedBy: "division", targetEntity: IncidentContact::class, cascade: ["remove"])]
    private Collection $incidentContacts;

    /**
     * Users associated with this division. Users can be associated
     * with multiple divisions at the same time. Each user has the same
     * role in all of its associated divisions.
     */
    #[ManyToMany(targetEntity: User::class, mappedBy: "divisions")]
    private Collection $users;

    /**
     * Event filters associated with this division.
     * Each filter can only be associated with a single division.
     */
    #[OneToMany(mappedBy: "division", targetEntity: EventFilter::class, cascade: ["remove"])]
    private Collection $eventFilters;

    public function __construct() {
        $this->sensors = new ArrayCollection();
        $this->incidentContacts = new ArrayCollection();
        $this->users = new ArrayCollection();
    }

    public function getId(): int {
        return $this->id;
    }

    /**
     * Returns all sensors associated with this division.
     */
    public function getSensors(): Collection {
        return $this->sensors;
    }

    /**
     * Add an incident contact to this division.
     */
    public function addIncidentContact(IncidentContact $contact): void {
        $this->incidentContacts[] = $contact;
        $contact->division = $this;
    }

    /**
     * Removes an incident contact from this division.
     */
    public function removeIncidentContact(IncidentContact $contact): void {
        $this->incidentContacts->removeElement($contact);
    }

    /**
     * Returns all incident contacts associated with this division.
     */
    public function getIncidentContacts(): Collection {
        return $this->incidentContacts;
    }

    /**
     * Associates a user with this division.
     */
    public function addUser(User $user): void {
        $this->users[] = $user;
        $user->divisions[] = $this;
    }

    /**
     * Dissociates a user from this division.
     */
    public function removeUser(User $user): void {
        $this->users->removeElement($user);
        $user->divisions->removeElement($this);
    }

    /**
     * Returns all users associated with this division.
     */
    public function getUsers(): Collection {
        return $this->users;
    }

    /**
     * Adds an event filter to this division.
     */
    public function addEventFilter(EventFilter $filter): void {
        $this->eventFilters[] = $filter;
        $filter->division = $this;
    }

    /**
     * Removes an event filter from this division.
     */
    public function removeEventFilter(EventFilter $filter): void {
        $this->eventFilters->removeElement($filter);
    }

    /**
     * Returns all event filters associated with this division.
     */
    public function getEventFilters(): Collection {
        return $this->eventFilters;
    }

    public function getState(): array {
        $users = array();
        foreach($this->users as $user) {
            $users[] = $user->getId();
        }
        return array(
            'id' => $this->id ?? null,
            'name' => $this->name,
            'users' => $users
        );
    }
}
