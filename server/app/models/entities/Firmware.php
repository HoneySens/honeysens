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
 * Represents a single firmware revision for a particular sensor platform.
 */
#[Entity]
#[Table(name: "firmware")]
class Firmware {

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    private int $id;

    /**
     * The name of this sensor firmware revision.
     */
    #[Column(type: "string")]
    public string $name;

    /**
     * Version string of this firmware revision.
     */
    #[Column(type: "string")]
    public string $version;

    /**
     * A short description of this firmware revision.
     */
    #[Column(type: "string")]
    public string $description;

    /**
     * The long description of changes that occured within this version
     */
    #[Column(type: "string")]
    public string $changelog;

    /**
     * The platform this firmware revision is compatible with.
     */
    #[ManyToOne(targetEntity: Platform::class, inversedBy: "firmwareRevisions")]
    public Platform $platform;

    public function getId(): int {
        return $this->id;
    }

    /**
     * Get the file name of this firmware archive (as found on the disk within the firmware data directory).
     * Returns a name based on the firmware ID.
     */
    public function getSource(): string {
        return sprintf('%s.tar.gz', $this->getId());
    }

    public function getState(): array {
        return array(
            'id' => $this->id ?? null,
            'name' => $this->name,
            'version' => $this->version,
            'description' => $this->description,
            'changelog' => $this->changelog,
            'platform' => $this->platform->getId()
        );
    }
}
