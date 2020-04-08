<?php
namespace HoneySens\app\models\entities;
use Doctrine\Common\Collections\ArrayCollection;
use HoneySens\app\models\exceptions\NotFoundException;
use NoiseLabs\ToolKit\ConfigParser\ConfigParser;

/**
 * Hardware platform abstraction.
 *
 * @Entity
 * @InheritanceType("SINGLE_TABLE")
 * @Table(name="platforms")
 * @DiscriminatorColumn(name="discr", type="string")
 * @DiscriminatorMap({
 *     "bbb" = "HoneySens\app\models\platforms\BeagleBoneBlack",
 *     "docker_x86" = "HoneySens\app\models\platforms\DockerX86"
 * })
 */
abstract class Platform {

    const FIRMWARE_PATH = 'firmware';

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * Unique, lower-case name for this platform (also used as a reference by external parties, e.g. services).
     *
     * @Column(type="string", nullable=false)
     */
    protected $name;

    /**
     * Informal name of this platform.
     *
     * @Column(type="string")
     */
    protected $title;

    /**
     * General description of this platform.
     *
     * @Column(type="string")
     */
    protected $description;

    /**
     * References the firmware revisions that are registered for this platform.
     *
     * @OneToMany(targetEntity="HoneySens\app\models\entities\Firmware", mappedBy="platform", cascade={"remove"})
     */
    protected $firmwareRevisions;

    /**
     * @OneToOne(targetEntity="HoneySens\app\models\entities\Firmware")
     */
    protected $defaultFirmwareRevision;

    public function __construct($name, $title) {
        $this->name = $name;
        $this->title = $title;
        $this->firmwareRevisions = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    public function getName() {
        return $this->name;
    }

    public function getTitle() {
        return $this->title;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    /**
     * Add a firmware file to this platform.
     * Returns a string that uniquely identifies the firmware (e.g. its location on disk).
     *
     * @param Firmware $firmware
     * @param string $filePath
     * @param ConfigParser $config
     */
    public function registerFirmware(Firmware $firmware, $filePath, ConfigParser $config) {
        rename($filePath, sprintf('%s/%s', $this->getFirmwarePath($config), $firmware->getSource()));
        $firmware->setSource(null);
    }

    /**
     * Removes the firmware file (if any) from a given firmware revision.
     *
     * @param Firmware $firmware
     * @param ConfigParser $config
     */
    public function unregisterFirmware(Firmware $firmware, ConfigParser $config) {
        if($this->isFirmwarePresent($firmware, $config))
            unlink(sprintf('%s/%s', $this->getFirmwarePath($config), $firmware->getSource()));
    }

    /**
     * Checks if the firmware data (source) is registered and available.
     *
     * @param Firmware $firmware
     * @param ConfigParser $config
     * @return bool
     */
    public function isFirmwarePresent(Firmware $firmware, ConfigParser $config) {
        return $firmware->getSource() != null && file_exists(sprintf('%s/%s', $this->getFirmwarePath($config), $firmware->getSource()));
    }

    /**
     * Returns the URI that can be used to download the firmware from the server.
     *
     * @param Firmware $firmware
     * @return string
     */
    public function getFirmwareURI(Firmware $firmware) {
        return '/api/platforms/firmware/' . $firmware->getId() . '/raw';
    }

    /**
     * Returns the full path to the raw data file that belongs to this firmware.
     *
     * @param Firmware $firmware
     * @param ConfigParser $config
     * @return mixed
     * @throws NotFoundException
     */
    public function obtainFirmware(Firmware $firmware, ConfigParser $config) {
        if(!$this->isFirmwarePresent($firmware, $config)) throw new NotFoundException();
        return sprintf('%s/%s', $this->getFirmwarePath($config), $firmware->getSource());
    }

    /**
     * Adds a firmware revision to this platform.
     *
     * @param Firmware $firmware
     * @return $this
     */
    public function addFirmwareRevision(Firmware $firmware) {
        $this->firmwareRevisions[] = $firmware;
        $firmware->setPlatform($this);
        return $this;
    }

    /**
     * Removes a firmware revision from this platform.
     *
     * @param Firmware $firmware
     * @return $this
     */
    public function removeFirmwareRevision(Firmware $firmware) {
        $this->firmwareRevisions->removeElement($firmware);
        $firmware->setPlatform(null);
        return $this;
    }

    /**
     * Get all firmware revisions associated with this platform.
     *
     * @return ArrayCollection
     */
    public function getFirmwareRevisions() {
        return $this->firmwareRevisions;
    }

    /**
     * Set the default firmware revision for this platform.
     *
     * @param Firmware|null $revision
     * @return $this
     */
    public function setDefaultFirmwareRevision($revision) {
        $this->defaultFirmwareRevision = $revision;
        return $this;
    }

    /**
     * Returns the firmware revision that this service defaults to.
     *
     * @return Firmware
     */
    public function getDefaultFirmwareRevision() {
        return $this->defaultFirmwareRevision;
    }

    /**
     * Returns true if a default firmware revision is attached to this platform.
     *
     * @return bool
     */
    public function hasDefaultFirmwareRevision() {
        return $this->defaultFirmwareRevision != null;
    }

    public function getState() {
        $firmwareRevisions = array();
        foreach($this->firmwareRevisions as $revision) {
            $firmwareRevisions[] = $revision->getState();
        }
        $defaultFirmwareRevision = $this->getDefaultFirmwareRevision() ? $this->getDefaultFirmwareRevision()->getId() : null;
        return array(
            'id' => $this->getId(),
            'name' => $this->getName(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'firmware_revisions' => $firmwareRevisions,
            'default_firmware_revision' => $defaultFirmwareRevision
        );
    }

    private function getFirmwarePath($config) {
        return sprintf('%s/%s', DATA_PATH, self::FIRMWARE_PATH);
    }
}