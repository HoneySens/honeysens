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
    protected int $id;

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
                return $e->getClassification() == $this->value;
                break;
            case EventFilterConditionField::SOURCE:
                switch($this->type) {
                    case EventFilterConditionType::SOURCE_VALUE:
                        return $e->getSource() == $this->value;
                    case EventFilterConditionType::SOURCE_IPRANGE:
                        $value = explode("-", $this->value);
                        return $e->getSource() >= trim($value[0]) && $e->getSource() <= trim($value[1]);
                }
                break;
            case EventFilterConditionField::TARGET:
                switch($this->type) {
                    case EventFilterConditionType::TARGET_PORT:
                        $port = null;
                        foreach($e->getPackets() as $packet) {
                            if($port == null || $port == $packet->getPort()) {
                                $port = $packet->getPort();
                            } else {
                                // more than two different target ports in packet list -> no match for a single port possible
                                return false;
                            }
                        }
                        return $port == $this->value;
                }
                break;
            case EventFilterConditionField::PROTOCOL:
                $packetCounts = array(EventPacket::PROTOCOL_UNKNOWN => 0, EventPacket::PROTOCOL_TCP => 0, EventPacket::PROTOCOL_UDP => 0);
                $packets = $e->getPackets();
                foreach($packets as $packet) {
                    $packetCounts[$packet->getProtocol()] += 1;
                }
                // only check if protocol is unique in the package list
                if(($packetCounts[EventPacket::PROTOCOL_TCP] > 0 && $packetCounts[EventPacket::PROTOCOL_UDP] == 0 && $packetCounts[EventPacket::PROTOCOL_UNKNOWN] == 0)
                    || ($packetCounts[EventPacket::PROTOCOL_UDP] > 0 && $packetCounts[EventPacket::PROTOCOL_TCP] == 0 && $packetCounts[EventPacket::PROTOCOL_UNKNOWN] == 0)) {
                    return $packets[0]->getProtocol() == $this->value;
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
