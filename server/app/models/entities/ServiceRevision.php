<?php
namespace HoneySens\app\models\entities;

/**
 * @entity
 * @Table(name="service_revisions")
 */
class ServiceRevision {

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * Revision string of this service, equals the "tag" of this particular docker image.
     *
     * @Column(type="string", nullable=false)
     */
    protected $revision;

    /**
     * The CPU architecture this service revision relies on.
     *
     * @Column(type="string")
     */
    protected $architecture;

    /**
     * Whether this revision requires raw network access (handled by the sensor).
     *
     * @Column(type="boolean")
     */
    protected $rawNetworkAccess;

    /**
     * Whether this revision acts as an catch-all service for packets that haven't been handled
     * by other services.
     *
     * @Column(type="boolean")
     */
    protected $catchAll;

    /**
     * TCP port redirections to expose service ports on the sensor.
     * Currently saved as JSON object string to just pass to the client.
     * Example: "{2222: 22}"
     * TODO This should be specific to service assignments
     *
     * @Column(type="string")
     */
    protected $portAssignment;

    /**
     * Description of this particular revision, mainly used to distinguish it from others.
     *
     * @Column(type="string")
     */
    protected $description;

    /**
     * @ManyToOne(targetEntity="HoneySens\app\models\entities\Service", inversedBy="revisions")
     */
    protected $service;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Set the revision string for this particular revision/version.
     *
     * @param string $revision
     * @return $this
     */
    public function setRevision($revision) {
        $this->revision = $revision;
        return $this;
    }

    /**
     * Get the revision string for this instance.
     *
     * @return string
     */
    public function getRevision() {
        return $this->revision;
    }

    /**
     * Set architecture
     *
     * @param string $architecture
     * @return $this
     */
    public function setArchitecture($architecture) {
        $this->architecture = $architecture;
        return $this;
    }

    /**
     * Get architecture
     *
     * @return string
     */
    public function getArchitecture() {
        return $this->architecture;
    }

    /**
     * Enable or disable raw network access for this revision
     *
     * @param boolean $rawNetworkAccess
     * @return $this
     */
    public function setRawNetworkAccess($rawNetworkAccess) {
        $this->rawNetworkAccess = $rawNetworkAccess;
        return $this;
    }

    /**
     * Get raw network access status
     *
     * @return boolean
     */
    public function getRawNetworkAccess() {
        return $this->rawNetworkAccess;
    }

    /*
     * Enable or disable catch-all demands of this revision
     */
    public function setCatchAll($catchAll) {
        $this->catchAll = $catchAll;
        return $this;
    }

    /**
     * Get the catch-all flag for this revision
     *
     * @return boolean
     */
    public function getCatchAll() {
        return $this->catchAll;
    }

    /**
     * Set a port assignment string.
     *
     * @param string $portAssignment
     * @return $this
     */
    public function setPortAssignment($portAssignment) {
        $this->portAssignment = $portAssignment;
        return $this;
    }

    /**
     * Get the current port assignment.,
     *
     * @return string
     */
    public function getPortAssignment() {
        return $this->portAssignment;
    }

    /**
     * Set a string that describes this revision, for instance with a version or change history.
     *
     * @param string $description
     * @return $this
     */
    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }

    /**
     * Get the revision description.
     *
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Set the service that belongs to this revision.
     *
     * @param Service|null $service
     * @return $this
     */
    public function setService(Service $service = null) {
        $this->service = $service;
        return $this;
    }

    /**
     * Get the service that belongs to this revision.
     *
     * @return Service
     */
    public function getService() {
        return $this->service;
    }

    public function getState() {
        return array(
            'id' => $this->getId(),
            'revision' => $this->getRevision(),
            'architecture' => $this->getArchitecture(),
            'raw_network_access' => $this->getRawNetworkAccess(),
            'catch_all' => $this->getCatchAll(),
            'description' => $this->getDescription(),
            'service' => $this->getService()->getId()
        );
    }
}