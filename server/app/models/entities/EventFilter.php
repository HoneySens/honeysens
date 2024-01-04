<?php
namespace HoneySens\app\models\entities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 * @ORM\Table(name="event_filters")
 */
class EventFilter {

    const TYPE_WHITELIST = 0;

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="HoneySens\app\models\entities\Division", inversedBy="eventFilters")
     */
    protected $division;

    /**
     * @ORM\Column(type="string")
     */
    protected $name;

    /**
     * The type of this filter
     *
     * @ORM\Column(type="integer")
     */
    protected $type;

    /**
     * Free-form text field describing this filter.
     *
     * @ORM\Column(type="string", nullable=true)
     */
    protected $description;

    /**
     * Counts the collected packages that were collected by this filter
     *
     * @ORM\Column(type="integer")
     */
    protected $count = 0;

    /**
     * @ORM\OneToMany(targetEntity="HoneySens\app\models\entities\EventFilterCondition", mappedBy="filter", cascade={"remove"})
     */
    protected $conditions;

    /**
     * Whether this filter is currently evaluated when processing incoming events.
     *
     * @ORM\Column(type="boolean")
     */
    protected $enabled = true;

    public function __construct() {
        $this->conditions = new ArrayCollection();
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
     * @return Division
     */
    public function getDivision() {
        return $this->division;
    }

    /**
     * Set the name of this filter
     *
     * @param string $name
     * @return $this
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the name of this filter
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set the type of this event filter
     *
     * @param integer $type
     * @return $this
     */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    /**
     * Return the type of this event filter
     *
     * @return integer
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Set a description for this event filter.
     *
     * @param string $description
     * @return $this
     */
    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }

    /**
     * Returns the description of this event filter, if there is any.
     *
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Adds an arbitrary number to the current counter value
     *
     * @param integer $amount
     */
    public function addToCount($amount) {
        $this->count += $amount;
    }

    /**
     * Returns the current packet coutner for this filter
     *
     * @return int
     */
    public function getCount() {
        return $this->count;
    }

    /**
     * Add a condition to this filter
     *
     * @param EventFilterCondition $condition
     * @return $this
     */
    public function addCondition(EventFilterCondition $condition) {
        $this->conditions[] = $condition;
        $condition->setFilter($this);
        return $this;
    }

    /**
     * Remove a condition from this filter
     *
     * @param EventFilterCondition $condition
     * @return $this
     */
    public function removeCondition(EventFilterCondition $condition) {
        $this->conditions->removeElement($condition);
        $condition->setFilter(null);
        return $this;
    }

    /**
     * Returns all conditions associated with this filter
     *
     * @return ArrayCollection
     */
    public function getConditions() {
        return $this->conditions;
    }

    /**
     * Enable or disable this filter.
     */
    public function setEnabled($enabled) {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * Returns the status (enabled/disabled) of this filter.
     *
     * @return boolean
     */
    public function isEnabled() {
        return $this->enabled;
    }

    /**
     * Runs all filter conditions against the given event.
     * Returns true if ALL conditions did match (logical AND) and there exists at least one condition.
     *
     * @param $event
     * @return bool
     */
    public function matches($event) {
        if(count($this->conditions) == 0) return false;
        foreach($this->conditions as $condition) {
            if(!$condition->matches($event)) return false;
        }
        return true;
    }

    public function getState() {
        $division = $this->getDivision() == null ? null : $this->getDivision()->getId();
        $conditions = array();
        foreach($this->conditions as $condition) {
            $conditions[] = $condition->getState();
        }
        return array(
            'id' => $this->getId(),
            'division' => $division,
            'name' => $this->getName(),
            'type' => $this->getType(),
            'description' => $this->getDescription(),
            'count' => $this->getCount(),
            'conditions' => $conditions,
            'enabled' => $this->isEnabled()
        );
    }
}
