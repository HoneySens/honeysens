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
 * A filter condition that belongs to a certain filter.
 * Conditions always belong to a single event attribute and store a regular expression that is used to check the condition.
 */
#[Entity]
#[Table(name: "event_filter_conditions")]
class EventFilterCondition {

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    protected $id;

    #[ManyToOne(targetEntity: EventFilter::class, inversedBy: "conditions")]
    protected $filter;

    /**
     * Specifies the event attribute that should be tested by this condition
     */
    #[Column(type: Types::INTEGER)]
    protected $field;

    /**
     * The condition type specifies the way the value should be interpreted
     */
    #[Column(type: Types::INTEGER)]
    protected $type;

    /**
     * The filter value of this condition, e.g. an regular expression or a string
     */
    #[Column(type: Types::STRING)]
    protected $value;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set event filter this condition belongs to
     *
     * @param EventFilter|null $filter
     * @return $this
     */
    public function setFilter(EventFilter $filter = null) {
        $this->filter = $filter;
        return $this;
    }

    /**
     * Get event filter this condition belongs to
     *
     * @return EventFilter|null
     */
    public function getFilter() {
        return $this->filter;
    }

    /**
     * Set the condition type
     *
     * @param $type
     * @return $this
     */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    /**
     * Returns the condition type
     *
     * @return mixed
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Set the event attribute this condition applies to
     *
     * @param string $name
     * @return $this
     */
    public function setField($name) {
        $this->field = $name;
        return $this;
    }

    /**
     * Return the event attribute this condition applies to
     *
     * @return string
     */
    public function getField() {
        return $this->field;
    }

    /**
     * Set the filter value
     *
     * @param string $value
     * @return $this
     */
    public function setValue($value) {
        $this->value = $value;
        return $this;
    }

    /**
     * Return the filter value
     *
     * @return string
     */
    public function getValue() {
        return $this->value;
    }

    /**
     * Applies this filter condition to the given event and returns the result
     *
     * @param Event $e
     * @return bool
     */
    public function matches(Event $e) {
        switch($this->field) {
            case EventFilterConditionField::CLASSIFICATION:
                return $e->getClassification() == $this->value;
                break;
            case EventFilterConditionField::SOURCE:
                switch($this->type) {
                    case EventFilterConditionType::SOURCE_VALUE:
                        return $e->getSource() == $this->value;
                        break;
                    case EventFilterConditionType::SOURCE_IPRANGE:
                        $value = explode("-", $this->value);
                        return $e->getSource() >= trim($value[0]) && $e->getSource() <= trim($value[1]);
                        break;
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
                        break;
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

    public function getState() {
        $filter = $this->getFilter() == null ? null : $this->getFilter()->getId();
        return array(
            'id' => $this->getId(),
            'filter' => $filter,
            'field' => $this->getField(),
            'type' => $this->getType(),
            'value' => $this->getValue()
        );
    }
}
