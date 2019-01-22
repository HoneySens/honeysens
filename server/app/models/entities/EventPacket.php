<?php
namespace HoneySens\app\models\entities;

/**
 * An IP packet that belongs to a certain event.
 *
 * @Entity
 * @Table(name="event_packets")
 */
class EventPacket {

    const PROTOCOL_UNKNOWN = 0;
    const PROTOCOL_TCP = 1;
    const PROTOCOL_UDP = 2;

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="HoneySens\app\models\entities\Event", inversedBy="packets")
     */
    protected $event;

    /**
     * When this event took place/packet was received
     *
     * @Column(type="datetime")
     */
    protected $timestamp;

    /**
     * The layer-4 protocol of this packet, currently TCP and UDP are supported.
     *
     * @Column(type="integer")
     */
    protected $protocol;

    /**
     * IP port number of this packet
     *
     * @Column(type="integer")
     */
    protected $port;

    /**
     * Relevant header fields of this packet, stored as a serialized JSON string.
     *
     * @Column(type="string", nullable=true)
     */
    protected $headers;

    /**
     * The packet binary payload, encoded in base64.
     *
     * @Column(type="string", nullable=true)
     */
    protected $payload;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set event
     *
     * @param Event $event
     * @return EventPacket
     */
    public function setEvent(Event $event = null) {
        $this->event = $event;
        return $this;
    }

    /**
     * Get event
     *
     * @return Event
     */
    public function getEvent() {
        return $this->event;
    }

    /**
     * Set timestamp
     *
     * @param \DateTime $timestamp
     * @return EventPacket
     */
    public function setTimestamp(\DateTime $timestamp = null) {
        $this->timestamp = $timestamp;
        return $this;
    }

    /**
     * Get timestamp
     *
     * @return \DateTime
     */
    public function getTimestamp() {
        return $this->timestamp;
    }

    /**
     * Set protocol
     *
     * @param integer $protocol
     * @return EventPacket
     */
    public function setProtocol($protocol) {
        $this->protocol = $protocol;
        return $this;
    }

    /**
     * Get protocol
     *
     * @return integer
     */
    public function getProtocol() {
        return $this->protocol;
    }

    /**
     * Set port number for this packet
     *
     * @param integer $port
     * @return EventPacket
     */
    public function setPort($port) {
        $this->port = $port;
        return $this;
    }

    /**
     * Get port number
     *
     * @return integer
     */
    public function getPort() {
        return $this->port;
    }

    /**
     * Add header field information
     *
     * @param string $field
     * @param string $value
     * @return EventPacket
     */
    public function addHeader($field, $value) {
        $headers = json_decode($this->headers, true);
        $headers[$field] = $value;
        $this->headers = json_encode($headers);
        return $this;
    }

    /**
     * Remove header field information
     *
     * @param string $field
     * @return EventPacket
     */
    public function removeHeader($field) {
        $headers = json_decode($this->headers, true);
        unset($headers[$field]);
        $this->headers = json_encode($headers);
        return $this;
    }

    /**
     * Returns all header fields and values as an associative array
     *
     * @return array
     */
    public function getHeaders() {
        return $this->headers;
    }

    /**
     * Sets payload data, which has to be already encoded as an base64 string
     *
     * @param string $payload
     * @return EventPacket
     */
    public function setPayload($payload) {
        $this->payload = $payload;
        return $this;
    }

    /**
     * Returns payload data as an base64 encoded string
     *
     * @return string
     */
    public function getPayload() {
        return $this->payload;
    }

    public function getState() {
        $event = $this->getEvent() == null ? null : $this->getEvent()->getId();
        return array(
            'id' => $this->getId(),
            'event' => $event,
            'timestamp' => $this->getTimestamp()->format('U'),
            'protocol' => $this->getProtocol(),
            'port' => $this->getPort(),
            'headers' => $this->getHeaders(),
            'payload' => $this->getPayload()
        );
    }
}
