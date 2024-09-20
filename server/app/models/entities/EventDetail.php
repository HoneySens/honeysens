<?php
namespace HoneySens\app\models\entities;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

#[Entity]
#[Table(name: "event_details")]
class EventDetail {

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    protected $id;

    #[ManyToOne(targetEntity: Event::class, inversedBy: "details")]
    protected $event;

    /**
     * An optional timestamp to track the attacker-sensor interaction
     */
    #[Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    protected $timestamp;

    /**
     * The type of data of these event details
     */
    #[Column(type: Types::INTEGER)]
    protected $type;

    #[Column(type: Types::STRING)]
    protected $data;

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
     * @param \HoneySens\app\models\entities\Event $event
     * @return EventDetails
     */
    public function setEvent(\HoneySens\app\models\entities\Event $event = null) {
        $this->event = $event;
        return $this;
    }

    /**
     * Get event
     *
     * @return \HoneySens\app\models\entities\Event
     */
    public function getEvent() {
        return $this->event;
    }

    /**
     * Set timestamp
     *
     * @param \DateTime $timestamp
     * @return EventDetails
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
     * Set type
     *
     * @param integer $type
     * @return EventDetails
     */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    /**
     * Get type
     *
     * @return integer
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Set data
     *
     * @param string $data
     * @return EventDetails
     */
    public function setData($data) {
        $this->data = substr($data, 0, 255);
        return $this;
    }

    /**
     * Get data
     *
     * @return string
     */
    public function getData() {
        return $this->data;
    }

    public function getState() {
        $timestamp = $this->getTimestamp() === null ? null : $this->getTimestamp()->format('U');
        return array(
            'id' => $this->getId(),
            'timestamp' => $timestamp,
            'type' => $this->getType(),
            'data' => $this->getData()
        );
    }
}
