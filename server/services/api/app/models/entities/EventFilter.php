<?php
namespace HoneySens\app\models\entities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

/**
 * Filters match hardcoded conditions such as source IP addresses or
 * protocols against incoming event reports. Incoming events that match
 * all conditions of a filter are silently discarded, but increase the
 * filter event count (to monitor that the filter actually works).
 * This facility is intended to be used in presence of recurring
 * but irrelevant event reports that can't be suppressed by other means.
 */
#[Entity]
#[Table(name: "event_filters")]
class EventFilter {

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    private int $id;

    /**
     * The division this event filter is associated with.
     */
    #[ManyToOne(targetEntity: Division::class, inversedBy: "eventFilters")]
    public Division $division;

    /**
     * Display name of this event filter.
     */
    #[Column(type: Types::STRING)]
    public string $name;

    /**
     * Free-form text field describing the purpose of this filter.
     */
    #[Column(type: Types::STRING, nullable: true)]
    public ?string $description;

    /**
     * Counts the collected events that were collected by
     * this filter for monitoring purposes.
     */
    #[Column(type: Types::INTEGER)]
    public int $count = 0;

    /**
     * A list of filter conditions. This filter applies only to
     * events that match all conditions.
     */
    #[OneToMany(mappedBy: "filter", targetEntity: EventFilterCondition::class, cascade: ["remove"])]
    private Collection $conditions;

    /**
     * Whether this filter is currently evaluated when processing incoming events.
     */
    #[Column(type: Types::BOOLEAN)]
    public bool $enabled = true;

    public function __construct() {
        $this->conditions = new ArrayCollection();
    }

    public function getId(): int {
        return $this->id;
    }

    /**
     * Increments the event counter for this filter by one.
     */
    public function incrementCounter(): void {
        $this->count += 1;
    }

    /**
     * Adds a condition to this event filter.
     */
    public function addCondition(EventFilterCondition $condition): void {
        $this->conditions[] = $condition;
        $condition->filter = $this;
    }

    /**
     * Removes a condition from this event filter.
     */
    public function removeCondition(EventFilterCondition $condition): void {
        $this->conditions->removeElement($condition);
    }

    /**
     * Returns all conditions associated with this event filter.
     */
    public function getConditions(): Collection {
        return $this->conditions;
    }

    /**
     * Evaluates all filter conditions against the given event.
     * Returns true if ALL conditions did match (logical AND) and there exists at least one condition.
     */
    public function matches(Event $event): bool {
        if(count($this->conditions) == 0) return false;
        foreach($this->conditions as $condition) {
            if(!$condition->matches($event)) return false;
        }
        return true;
    }

    public function getState(): array {
        $conditions = array();
        foreach($this->conditions as $condition) {
            $conditions[] = $condition->getState();
        }
        return array(
            'id' => $this->id ?? null,
            'division' => $this->division->getId(),
            'name' => $this->name,
            'description' => $this->description,
            'count' => $this->count,
            'conditions' => $conditions,
            'enabled' => $this->enabled
        );
    }
}
