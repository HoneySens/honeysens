<?php
namespace HoneySens\app\models\entities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\Table;

/**
 * Represents a dockerized honeypot service.
 * Implementations are associated as revisions to support switching between
 * different versions of this service. Distribution of service revisions to
 * sensors is represented by service assignments.
 */
#[Entity]
#[Table(name: "services")]
class Service {

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    private int $id;

    /**
     * Informal title of this service.
     */
    #[Column(type: Types::STRING, nullable: false)]
    public string $name;

    /**
     * General description of this service.
     */
    #[Column(type: Types::STRING)]
    public string $description;

    /**
     * Docker repository that relates to this service, e.g. "honeysens/cowrie".
     */
    #[Column(type: Types::STRING)]
    public string $repository;

    /**
     * References the docker image tags for this service.
     */
    #[OneToMany(targetEntity: ServiceRevision::class, mappedBy: "service", cascade: ["remove"])]
    private Collection $revisions;

    /**
     * The revision that this service defaults to. It is used
     * in all service assignments that don't specify their own revision.
     */
    #[Column(type: Types::STRING, nullable: true)]
    public string $defaultRevision;

    /**
     * The service assignment that this service is associated with.
     */
    #[OneToMany(targetEntity: ServiceAssignment::class, mappedBy: "service", cascade: ["remove"])]
    private Collection $assignments;

    public function __construct() {
        $this->revisions = new ArrayCollection();
        $this->assignments = new ArrayCollection();
    }

    public function getId(): int {
        return $this->id;
    }

    /**
     * Returns a label based on the repository name of this service,
     * which may be used on the sensor as service identifier.
     */
    public function getLabel(): string {
        $nameParts = explode('/', $this->repository);
        return $nameParts[sizeof($nameParts) - 1];
    }

    /**
     * Add a revision to this service.
     */
    public function addRevision(ServiceRevision $revision): void {
        $this->revisions[] = $revision;
        $revision->service = $this;
    }

    /**
     * Get all revisions associated with this service.
     */
    public function getRevisions(): Collection {
        return $this->revisions;
    }

    /**
     * Returns all distinct revisions and their respective architectures in the form
     * array($rev1 => array($arch1 => $r1, $arch2 => $r2, ...), ...)
     */
    public function getDistinctRevisions(): array {
        $result = array();
        foreach($this->revisions as $r) {
            $rev = $r->revision;
            $arch = $r->architecture;
            if(!array_key_exists($rev, $result)) $result[$rev] = array();
            if(!array_key_exists($arch, $result[$rev])) $result[$rev][$arch] = $r;
        }
        return $result;
    }

    /**
     * Assign this service with a specific sensor, causing it to run there.
     */
    public function addAssignment(ServiceAssignment $assignment): void {
        $this->assignments[] = $assignment;
        $assignment->service = $this;
    }

    /**
     * Removes the assignment of this service from a specific sensor, causing it to no longer run there.
     */
    public function removeAssignment(ServiceAssignment $assignment): void {
        $this->assignments->removeElement($assignment);
        //$assignment->service = null;
    }

    public function getState(): array {
        // Returns revisions grouped by version
        $versions = array();
        foreach($this->getDistinctRevisions() as $version => $r) {
            $serviceRevisions = array();
            $architectures = array();
            foreach($r as $arch => $serviceRevision) {
                $architectures[] = $arch;
                $serviceRevisions[] = $serviceRevision->getState();
            }
            $versions[] = array('id' => $version, 'architectures' => $architectures, 'revisions' => $serviceRevisions);
        }
        $assignments = array();
        foreach($this->assignments as $assignment) {
            $assignments[] = $assignment->getId();
        }
        return array(
            'id' => $this->getId(),
            'name' => $this->name,
            'description' => $this->description,
            'repository' => $this->repository,
            'versions' => $versions,
            'default_revision' => $this->defaultRevision,
            'assignments' => $assignments
        );
    }
}
