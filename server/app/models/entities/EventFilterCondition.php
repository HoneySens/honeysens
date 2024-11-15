<?php
namespace HoneySens\app\models\entities;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use HoneySens\app\models\constants\EventFilterConditionField;
use HoneySens\app\models\constants\EventFilterConditionType;
use HoneySens\app\models\constants\EventPacketProtocol;

/**
 * A single filter condition always belongs to an event filter.
 * Conditions always reference a specific event attribute/field and
 * an attribute-dependent expression that is used to evaluate the condition.
 */
#[Entity]
#[Table(name: "event_filter_conditions")]
class EventFilterCondition {

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    private int $id;

    /**
     * The higher-level filter this filter condition is associated with.
     */
    #[ManyToOne(targetEntity: EventFilter::class, inversedBy: "conditions")]
    public EventFilter $filter;

    /**
     * Specifies the event attribute that is tested by this condition.
     */
    #[Column()]
    public EventFilterConditionField $field;

    /**
     * The condition type specifies the way $value should be interpreted.
     */
    #[Column()]
    public EventFilterConditionType $type;

    /**
     * The filter value of this condition, e.g. a regular expression or a string.
     */
    #[Column(type: Types::STRING)]
    public string $value;

    public function getId(): int {
        return $this->id;
    }

    /**
     * Tests an event against this filter condition and returns the comparison result.
     */
    public function matches(Event $e): bool {
        switch($this->field) {
            case EventFilterConditionField::CLASSIFICATION:
                return $e->classification->value == $this->value;
                break;
            case EventFilterConditionField::SOURCE:
                switch($this->type) {
                    case EventFilterConditionType::SOURCE_VALUE:
                        return $e->source == $this->value;
                    case EventFilterConditionType::SOURCE_IPRANGE:
                        $value = explode("-", $this->value);
                        return $e->source >= trim($value[0]) && $e->source <= trim($value[1]);
                }
                break;
            case EventFilterConditionField::TARGET:
                switch($this->type) {
                    case EventFilterConditionType::TARGET_PORT:
                        $port = null;
                        foreach($e->getPackets() as $packet) {
                            if($port == null || $port == $packet->port) {
                                $port = $packet->port;
                            } else {
                                // more than two different target ports in packet list -> no match for a single port possible
                                return false;
                            }
                        }
                        return $port == $this->value;
                }
                break;
            case EventFilterConditionField::PROTOCOL:
                $packetCounts = array(
                    EventPacketProtocol::UNKNOWN->value => 0,
                    EventPacketProtocol::TCP->value => 0,
                    EventPacketProtocol::UDP->value => 0);
                $packets = $e->getPackets();
                foreach($packets as $packet) {
                    $packetCounts[$packet->protocol] += 1;
                }
                // only check if protocol is unique in the package list
                if(($packetCounts[EventPacketProtocol::TCP->value] > 0 && $packetCounts[EventPacketProtocol::UDP->value] == 0 && $packetCounts[EventPacketProtocol::UNKNOWN->value] == 0)
                    || ($packetCounts[EventPacketProtocol::UDP->value] > 0 && $packetCounts[EventPacketProtocol::TCP->value] == 0 && $packetCounts[EventPacketProtocol::UNKNOWN->value] == 0)) {
                    return $packets[0]->protocol == $this->value;
                }
                break;
        }
        return false;
    }

    public function getState(): array {
        return array(
            'id' => $this->id ?? null,
            'filter' => $this->filter->getId(),
            'field' => $this->field->value,
            'type' => $this->type->value,
            'value' => $this->value
        );
    }
}
