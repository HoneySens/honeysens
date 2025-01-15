<?php
namespace HoneySens\app\models\entities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;
use HoneySens\app\models\exceptions\NotFoundException;

/**
 * Sensor deployment platform abstraction.
 */
#[Entity]
#[Table(name: "platforms")]
#[InheritanceType("SINGLE_TABLE")]
#[DiscriminatorColumn(name: "discr", type: Types::STRING)]
#[DiscriminatorMap([
    "bbb" => "HoneySens\app\models\platforms\BeagleBoneBlack",
    "docker_x86" => "HoneySens\app\models\platforms\DockerX86"]
)]
abstract class Platform {

    const string FIRMWARE_PATH = 'firmware';

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    private int $id;

    /**
     * Unique, lower-case name for this platform (also used as a reference by external parties, e.g. services).
     */
    #[Column(type: Types::STRING, nullable: false)]
    public string $name;

    /**
     * Informal name of this platform.
     */
    #[Column(type: Types::STRING)]
    public string $title;

    /**
     * General description of this platform.
     */
    #[Column(type: Types::STRING)]
    public string $description;

    /**
     * References the firmware revisions that are registered for this platform.
     */
    #[OneToMany(mappedBy: "platform", targetEntity: Firmware::class, cascade: ["remove"])]
    private Collection $firmwareRevisions;

    /**
     * The global default revision to use for all sensors of this platform type that don't
     * overwrite this setting by specifying their own target firmware revision.
     * This should only be null in case no firmware revisions exist yet for this platform.
     */
    #[OneToOne(targetEntity: Firmware::class)]
    public ?Firmware $defaultFirmwareRevision;

    public function __construct(string $name, string $title) {
        $this->name = $name;
        $this->title = $title;
        $this->firmwareRevisions = new ArrayCollection();
    }

    public function getId(): int {
        return $this->id;
    }

    /**
     * Add a firmware file to this platform.
     * Returns a string that uniquely identifies the firmware (e.g. its location on disk).
     */
    public function registerFirmwareFile(Firmware $firmware, $filePath): void {
        rename($filePath, sprintf('%s/%s', $this->getFirmwarePath(), $firmware->getSource()));
    }

    /**
     * Removes the firmware file (if any) from a given firmware revision.
     */
    public function unregisterFirmwareFile(Firmware $firmware): void {
        if($this->isFirmwarePresent($firmware))
            unlink(sprintf('%s/%s', $this->getFirmwarePath(), $firmware->getSource()));
    }

    /**
     * Checks whether a data file (source) exists for a specific firmware revision.
     */
    public function isFirmwarePresent(Firmware $firmware): bool {
        return $firmware->getSource() !== null && file_exists(sprintf('%s/%s', $this->getFirmwarePath(), $firmware->getSource()));
    }

    /**
     * Returns a URI to download the firmware from the server.
     */
    public function getFirmwareURI(Firmware $firmware): string {
        return 'api/platforms/firmware/' . $firmware->getId() . '/raw';
    }

    /**
     * Returns the full path to the data file of a specific firmware.
     */
    public function obtainFirmware(Firmware $firmware): string {
        if(!$this->isFirmwarePresent($firmware)) throw new NotFoundException();
        return sprintf('%s/%s', $this->getFirmwarePath(), $firmware->getSource());
    }

    /**
     * Adds a firmware revision to this platform.
     */
    public function addFirmwareRevision(Firmware $firmware): void {
        $this->firmwareRevisions[] = $firmware;
        $firmware->platform = $this;
    }

    /**
     * Removes a firmware revision from this platform.
     */
    public function removeFirmwareRevision(Firmware $firmware): void {
        $this->firmwareRevisions->removeElement($firmware);
    }

    public function getState(): array {
        $firmwareRevisions = array();
        foreach($this->firmwareRevisions as $revision) {
            $firmwareRevisions[] = $revision->getState();
        }
        return array(
            'id' => $this->id ?? null,
            'name' => $this->name,
            'title' => $this->title,
            'description' => $this->description,
            'firmware_revisions' => $firmwareRevisions,
            'default_firmware_revision' => $this->defaultFirmwareRevision?->getId()
        );
    }

    private function getFirmwarePath(): string {
        return sprintf('%s/%s', DATA_PATH, self::FIRMWARE_PATH);
    }
}
