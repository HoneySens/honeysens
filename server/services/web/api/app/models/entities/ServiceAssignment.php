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
    private int $id;

    #[ManyToOne(targetEntity: Sensor::class, inversedBy: "sensors")]
    public Sensor $sensor;

    #[ManyToOne(targetEntity: Service::class, inversedBy: "assignments")]
    public Service $service;

    /**
     * Optional service revision in case this assignment should use a different
     * revision than this service's default one.
     */
    #[OneToOne(targetEntity: ServiceRevision::class)]
    public ?ServiceRevision $revision = null;

    public function getId(): int {
        return $this->id;
    }

    public function getState(): array {
        return array(
            'sensor' => $this->sensor->getId(),
            'service' => $this->service->getId(),
            'revision' => $this->revision?->getId()
        );
    }
}
