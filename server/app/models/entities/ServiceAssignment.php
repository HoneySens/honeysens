<?php
namespace HoneySens\app\models\entities;

/**
 * Associative class that assign a specific service (with a specific revision) to a sensor.
 *
 * @entity
 * @Table(name="service_assignments")
 */
class ServiceAssignment {

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="HoneySens\app\models\entities\Sensor", inversedBy="sensors")
     */
    protected $sensor;

    /**
     * @ManyToOne(targetEntity="HoneySens\app\models\entities\Service", inversedBy="assignments")
     */
    protected $service;

    /**
     * @OneToOne(targetEntity="HoneySens\app\models\entities\ServiceRevision")
     */
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
