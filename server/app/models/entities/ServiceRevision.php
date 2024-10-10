<?php
namespace HoneySens\app\models\entities;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\Table;

/**
 * A specific revision of a particular service,
 * parsed from the metadata received during the upload process.
 */
#[Entity]
#[Table(name: "service_revisions")]
class ServiceRevision {

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    private int $id;

    /**
     * Revision string of this service, equals the "tag" of its associated docker image.
     */
    #[Column(type: Types::STRING, nullable: false)]
    public string $revision;

    /**
     * The CPU architecture this service revision relies on.
     */
    #[Column(type: Types::STRING)]
    public string $architecture;

    /**
     * Whether this revision requires raw network access (handled by the sensor).
     */
    #[Column(type: Types::BOOLEAN)]
    public bool $rawNetworkAccess;

    /**
     * Whether this revision acts as a catch-all service for packets that haven't been handled
     * by other services.
     */
    #[Column(type: Types::BOOLEAN)]
    public bool $catchAll;

    /**
     * TCP port redirections to expose service ports on the sensor.
     * Currently saved as JSON object string to just pass to the client.
     * Example: "{2222: 22}"
     * TODO This should be a property of service assignments
     */
    #[Column(type: Types::STRING)]
    public string $portAssignment;

    /**
     * Description of this particular revision, used to informally distinguish it from others.
     * Typically set to a version string or used to contain a changelog.
     */
    #[Column(type: Types::STRING)]
    public string $description;

    /**
     * The service this revision belongs to.
     */
    #[ManyToOne(targetEntity: Service::class, inversedBy: "revisions")]
    public Service $service;

    public function getId(): int {
        return $this->id;
    }

    public function getState(): array {
        return array(
            'id' => $this->id ?? null,
            'revision' => $this->revision,
            'architecture' => $this->architecture,
            'raw_network_access' => $this->rawNetworkAccess,
            'catch_all' => $this->catchAll,
            'description' => $this->description,
            'service' => $this->service->getId()
        );
    }
}
