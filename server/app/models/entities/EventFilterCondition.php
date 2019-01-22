<?php
namespace HoneySens\app\models\entities;

/**
 * A filter condition that belongs to a certain filter.
 * Conditions always belong to a single event attribute and store a regular expression that is used to check the condition.
 *
 * @Entity
 * @Table(name="event_filter_conditions")
 */
class EventFilterCondition {

    const FIELD_CLASSIFICATION = 0; // Event::$classification
    const FIELD_SOURCE = 1;
    const FIELD_TARGET = 2;
    const FIELD_PROTOCOL = 3; // EventPacket::$protocol

    const TYPE_SOURCE_VALUE = 0;
    const TYPE_SOURCE_REGEX = 1;
    const TYPE_SOURCE_IPRANGE = 2;
    const TYPE_TARGET_PORT = 3;

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="HoneySens\app\models\entities\EventFilter", inversedBy="conditions")
     */
    protected $filter;

    /**
     * Specifies the event attribute that should be tested by this condition
     *
     * @Column(type="integer")
     */
    protected $field;

    /**
     * The condition type specifies the way the value should be interpreted
     *
     * @Column(type="integer")
     */
    protected $type;

    /**
     * The filter value of this condition, e.g. an regular expression or a string
     *
     * @Column(type="string")
     */
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
            case $this::FIELD_CLASSIFICATION:
                return $e->getClassification() == $this->value;
                break;
            case $this::FIELD_SOURCE:
                switch($this->type) {
                    case $this::TYPE_SOURCE_VALUE:
                        return $e->getSource() == $this->value;
                        break;
                    case $this::TYPE_SOURCE_IPRANGE:
                        $value = explode("-", $this->value);
                        return $e->getSource() >= trim($value[0]) && $e->getSource() <= trim($value[1]);
                        break;
                }
                break;
            case $this::FIELD_TARGET:
                switch($this->type) {
                    case $this::TYPE_TARGET_PORT:
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
            case $this::FIELD_PROTOCOL:
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