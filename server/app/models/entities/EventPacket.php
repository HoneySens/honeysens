<?php
namespace HoneySens\app\models\entities;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;
use HoneySens\app\models\constants\EventPacketProtocol;
use HoneySens\app\models\Utils;

/**
 * A timestamped network IP packet that
 * was received and reported by a sensor service.
 */
#[Entity]
#[Table(name: "event_packets")]
class EventPacket {

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    private int $id;

    /**
     * The event this network packet is associated with.
     */
    #[ManyToOne(targetEntity: Event::class, inversedBy: "packets")]
    public Event $event;

    /**
     * When this event took place/packet was received.
     */
    #[Column(type: Types::DATETIME_MUTABLE)]
    public \DateTime $timestamp;

    /**
     * The layer-4 protocol of this packet, currently only TCP and UDP are supported.
     * As fallback, this can be set to UNKNOWN in case a different protocol is reported.
     */
    #[Column()]
    public EventPacketProtocol $protocol;

    /**
     * TCP/UDP port number of this packet.
     */
    #[Column(type: Types::INTEGER)]
    public int $port;

    /**
     * Relevant header fields of this packet, stored as a serialized JSON string.
     * Which headers are recorded depends on the sensor service that generated this
     * event. For example, the recon service submits TCP flags.
     */
    #[Column(type: Types::STRING, nullable: true)]
    private ?string $headers = null;

    /**
     * The packet binary payload, encoded in base64.
     */
    #[Column(type: Types::STRING, nullable: true)]
    private ?string $payload;

    public function getId(): int {
        return $this->id;
    }

    /**
     * Adds a header field and value for this particular packet.
     */
    public function addHeader(string $field, $value): void {
        $headers = json_decode($this->headers, true);
        $headers[$field] = $value;
        $this->headers = json_encode($headers);
    }

    /**
     * Returns all header fields and values as a JSON string.
     */
    public function getHeaders(): string {
        return $this->headers;
    }

    /**
     * Sets payload data, which has to be an already encoded base64 string.
     * Truncates the (decoded) data to a limit of 255 characters.
     */
    public function setPayload(?string $payload): void {
        if($payload === null) $this->payload = null;
        else $this->payload = Utils::shortenBase64(255, $payload);
    }

    /**
     * Returns payload data as an base64 encoded string.
     */
    public function getPayload(): string {
        return $this->payload;
    }

    public function getState(): array {
        return array(
            'id' => $this->id ?? null,
            'event' => $this->event->getId(),
            'timestamp' => $this->timestamp->format('U'),
            'protocol' => $this->protocol->value,
            'port' => $this->port,
            'headers' => $this->getHeaders(),
            'payload' => $this->payload
        );
    }
}
