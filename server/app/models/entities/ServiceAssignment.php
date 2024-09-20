<?php
namespace HoneySens\app\models\entities;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;

/**
 * Associative class that assign a specific service (with a specific revision) to a sensor.
 */
#[Entity]
#[Table(name: "service_assignments")]
class ServiceAssignment {

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    protected $id;

    #[ManyToOne(targetEntity: Sensor::class, inversedBy: "sensors")]
    protected $sensor;

    #[ManyToOne(targetEntity: Service::class, inversedBy: "assignments")]
    protected $service;

    #[OneToOne(targetEntity: ServiceRevision::class)]
    protected $revision;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set the sensor that this service assignment refers to.
     *
     * @param Sensor|null $sensor
     * @return $this
     */
    public function setSensor(Sensor $sensor = null) {
        $this->sensor = $sensor;
        return $this;
    }

    /**
     * Get the sensor that belongs to this service assignment.
     *
     * @return Sensor
     */
    public function getSensor() {
        return $this->sensor;
    }

    /**
     * Set the service that this service assignment refers to.
     *
     * @param Service|null $service
     * @return $this
     */
    public function setService(Service $service = null) {
        $this->service = $service;
        return $this;
    }

    /**
     * Get the service that belongs to this service assignment.
     *
     * @return Service
     */
    public function getService() {
        return $this->service;
    }

    /**
     * Set the revision that this service assignment is supposed to use.
     *
     * @param ServiceRevision|null $revision
     * @return $this
     */
    public function setRevision(ServiceRevision $revision = null) {
        $this->revision = $revision;
        return $this;
    }

    /**
     * Get the revision that this service assignment is supposed to use.
     *
     * @return mixed
     */
    public function getRevision() {
        return $this->revision;
    }

    public function getState() {
        $revision = $this->getRevision() ? $this->getRevision()->getId() : null;
        return array(
            'sensor' => $this->getSensor()->getId(),
            'service' => $this->getService()->getId(),
            'revision' => $revision
        );
    }
}
