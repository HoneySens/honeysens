<?php
namespace HoneySens\app\models\entities;

/**
 * @Entity
 * @Table(name="firmware")
 */
class Firmware {
	
	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue 
	 */
	protected $id;
	
	/**
	 * The name of this sensor image
	 * 
	 * @Column(type="string")
	 */
	protected $name;
	
	/**
	 * Version string of this image
	 * 
	 * @Column(type="string")
	 */
	protected $version;
	
	/**
	 * A short description of this image
	 * 
	 * @Column(type="string")
	 */
	protected $description;
	
	/**
	 * The long description of changes that occured within this version
	 * 
	 * @Column(type="string")
	 */
	protected $changelog;
	
	/**
	 * Reference to this firmware image, format is platform-dependent
	 * 
	 * @Column(type="string")
	 */
	protected $source;
	
    /**
     * The platform this firmware revision belongs to.
     *
     * @ManyToOne(targetEntity="HoneySens\app\models\entities\Platform", inversedBy="firmwareRevisions")
     */
	protected $platform;
	
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }
	
    /**
     * Set name
     *
     * @param string $name
     * @return Firmware
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }
	
    /**
     * Set version
     *
     * @param string $version
     * @return Firmware
     */
    public function setVersion($version) {
        $this->version = $version;
        return $this;
    }

    /**
     * Get version
     *
     * @return string
     */
    public function getVersion() {
        return $this->version;
    }
	
    /**
     * Set description
     *
     * @param string $description
     * @return Firmware
     */
    public function setDescription($description) {
        $this->description = $description;
        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }
	
    /**
     * Set change log
     *
     * @param string $changelog
     * @return Firmware
     */
    public function setChangelog($changelog) {
        $this->changelog = $changelog;
        return $this;
    }

    /**
     * Get change log
     *
     * @return string
     */
    public function getChangelog() {
        return $this->changelog;
    }
	
    /**
     * Set source reference
     *
     * @param string $source
     * @return Firmware
     */
    public function setSource($source) {
        $this->source = $source;
        return $this;
    }

    /**
     * Get source reference
     *
     * @return string
     */
    public function getSource() {
        return $this->source;
    }
	
	/**
	 * Returns the name of the converted image file that is
	 * ready to be writen onto a SD card
	 */
	public function getConvertedFile() {
		return preg_replace('/\s+/', '-', strtolower((string) $this->name)) . '-' . preg_replace('/\s+/', '-', strtolower((string) $this->version)) . '.img';
	}

    /**
     * Set the platform this firmware revision belongs to.
     *
     * @param Platform|null $platform
     * @return $this
     */
	public function setPlatform(Platform $platform = null) {
	    $this->platform = $platform;
	    return $this;
    }

    /**
     * Get the platform that belongs to this firmware revision.
     *
     * @return Platform
     */
    public function getPlatform() {
	    return $this->platform;
    }
		
	public function getState() {
		return array(
			'id' => $this->getId(),
			'name' => $this->getName(),
			'version' => $this->getVersion(),
			'description' => $this->getDescription(),
			'changelog' => $this->getChangelog(),
			'source' => $this->getSource(),
            'platform' => $this->getPlatform()->getId()
		);
	}
}