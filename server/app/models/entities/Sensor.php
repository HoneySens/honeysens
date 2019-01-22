<?php
namespace HoneySens\app\models\entities;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Entity
 * @Table(name="sensors")
 */
class Sensor {

    const SERVER_ENDPOINT_MODE_DEFAULT = 0;
    const SERVER_ENDPOINT_MODE_CUSTOM = 1;

    const NETWORK_IP_MODE_DHCP = 0;
    const NETWORK_IP_MODE_STATIC = 1;
    const NETWORK_IP_MODE_NONE = 2;

    const NETWORK_MAC_MODE_ORIGINAL = 0;
    const NETWORK_MAC_MODE_CUSTOM = 1;

    const PROXY_MODE_DISABLED = 0;
    const PROXY_MODE_ENABLED = 1;

    const CONFIG_ARCHIVE_STATUS_UNAVAILABLE = 0;
    const CONFIG_ARCHIVE_STATUS_SCHEDULED = 1;
    const CONFIG_ARCHIVE_STATUS_CREATING = 2;
    const CONFIG_ARCHIVE_STATUS_AVAILABLE = 3;

	/**
	 * @Id
	 * @Column(type="integer")
	 * @GeneratedValue 
	 */
	protected $id;
	
	/**
	 * @Column(type="string")
	 */
	protected $name;

	/**
	 * @Column(type="string")
	 */
	protected $location;
	
	/**
	 * @OneToOne(targetEntity="HoneySens\app\models\entities\SSLCert", inversedBy="sensor", cascade={"remove"})
	 */
	protected $cert;
	
	/**
	 * @OneToMany(targetEntity="HoneySens\app\models\entities\SensorStatus", mappedBy="sensor", cascade={"remove"});
	 */
	protected $status;

	/**
	 * @ManyToOne(targetEntity="HoneySens\app\models\entities\Division", inversedBy="sensors")
	 */
	protected $division;

    /**
     * @Column(type="integer")
     */
    protected $serverEndpointMode;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $serverEndpointHost;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $serverEndpointPortHTTPS;

    /**
     * @Column(type="integer")
     */
    protected $networkIPMode;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $networkIPAddress;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $networkIPNetmask;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $networkIPGateway;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $networkIPDNS;

    /**
     * @Column(type="integer")
     */
    protected $networkMACMode;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $networkMACAddress;

    /**
     * @Column(type="integer")
     */
    protected $proxyMode;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $proxyHost;

    /**
     * @Column(type="integer", nullable=true)
     */
    protected $proxyPort;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $proxyUser;

    /**
     * @Column(type="string", nullable=true)
     */
    protected $proxyPassword;

    /**
     * @Column(type="integer")
     */
    protected $configArchiveStatus = 0;

    /**
     * Update interval in minutes.
     *
     * @Column(type="integer", nullable=true)
     */
    protected $updateInterval = null;

    /**
     * @ManyToOne(targetEntity="HoneySens\app\models\entities\Firmware")
     */
    protected $firmware;

    /**
     * The services that are configured to run on this sensor.
     *
     * @OneToMany(targetEntity="HoneySens\app\models\entities\ServiceAssignment", mappedBy="sensor")
     */
    protected $services;

    public function __construct() {
        $this->status = new ArrayCollection();
        $this->services = new ArrayCollection();
    }
	
    /**
     * Get id
     *
     * @return integer 
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Sensors use their id as hostname, preceded by an string, to conform to host and domain name conventions
     *
     * @return string
     */
    public function getHostname() {
        return 's' . $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Sensor
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
     * Set location
     *
     * @param string $location
     * @return Sensor
     */
    public function setLocation($location) {
        $this->location = $location;
        return $this;
    }

    /**
     * Get location
     *
     * @return string 
     */
    public function getLocation() {
        return $this->location;
    }

	/**
	 * Set cert
	 * 
	 * @param \HoneySens\app\models\entities\SSLCert $cert
	 * @return Sensor
	 */
	public function setCert(\HoneySens\app\models\entities\SSLCert $cert = null) {
		$this->cert = $cert;
		return $this;
	}
	
	/**
	 * Get cert
	 * 
	 * @return \HoneySens\app\models\entities\SSLCert
	 */
	public function getCert() {
		return $this->cert;
	}
	
	/**
	 * Add status info
	 * 
	 * @param \HoneySens\app\models\entities\SensorStatus $status
	 * @return Sensor
	 */
	public function addStatus(\HoneySens\app\models\entities\SensorStatus $status) {
		$this->status[] = $status;
		$status->setSensor($this);
		return $this;
	}
	
	/**
	 * Remove status info
	 * 
	 * @param \HoneySens\app\models\entities\SensorStatus $status
	 * @return Sensor
	 */
	public function removeStatus(\HoneySens\app\models\entities\SensorStatus $status) {
		$this->status->removeElement($status);
		$status->setSensor(null);
		return $this;
	}

    /**
     * Get all status info
     *
     * @return \HoneySens\app\models\entities\SensorStatus
     */
    public function getStatus() {
        return $this->status;
    }

    public function getLastStatus() {
        $statusSorted = array();
        foreach($this->status as $key => $status) {
            $statusSorted[$key] = $status;
            $timestamps[$key] = $status->getTimestamp();
        }
        if(count($statusSorted) > 0) {
            array_multisort($timestamps, SORT_DESC, $statusSorted);
            return $statusSorted[0];
        } else return null;
    }

	/**
	 * Set division
	 *
	 * @param Division $division
	 * @return $this
	 */
	public function setDivision(Division $division = null) {
		$this->division = $division;
		return $this;
	}

	/**
	 * Get division
	 *
	 * @return mixed
	 */
	public function getDivision() {
		return $this->division;
	}

    public function setServerEndpointMode($mode) {
        $this->serverEndpointMode = $mode;
        return $this;
    }

    public function getServerEndpointMode() {
        return $this->serverEndpointMode;
    }

    public function setServerEndpointHost($host) {
        $this->serverEndpointHost = $host;
        return $this;
    }

    public function getServerEndpointHost() {
        return $this->serverEndpointHost;
    }

    public function setServerEndpointPortHTTPS($port) {
        $this->serverEndpointPortHTTPS = $port;
        return $this;
    }

    public function getServerEndpointPortHTTPS() {
        return $this->serverEndpointPortHTTPS;
    }

    public function setNetworkIPMode($mode) {
        $this->networkIPMode = $mode;
        return $this;
    }

    public function getNetworkIPMode() {
        return $this->networkIPMode;
    }

    public function setNetworkIPAddress($address) {
        $this->networkIPAddress = $address;
        return $this;
    }

    public function getNetworkIPAddress() {
        return $this->networkIPAddress;
    }

    public function setNetworkIPNetmask($netmask) {
        $this->networkIPNetmask = $netmask;
        return $this;
    }

    public function getNetworkIPNetmask() {
        return $this->networkIPNetmask;
    }

    public function setNetworkIPGateway($gateway) {
        $this->networkIPGateway = $gateway;
        return $this;
    }

    public function getNetworkIPGateway() {
        return $this->networkIPGateway;
    }

    public function setNetworkIPDNS($dns) {
        $this->networkIPDNS = $dns;
        return $this;
    }

    public function getNetworkIPDNS() {
        return $this->networkIPDNS;
    }

    public function setNetworkMACMode($mode) {
        $this->networkMACMode = $mode;
        return $this;
    }

    public function getNetworkMACMode() {
        return $this->networkMACMode;
    }

    public function setNetworkMACAddress($address) {
        $this->networkMACAddress = $address;
        return $this;
    }

    public function getNetworkMACAddress() {
        return $this->networkMACAddress;
    }

    public function setProxyMode($mode) {
        $this->proxyMode = $mode;
        return $this;
    }

    public function getProxyMode() {
        return $this->proxyMode;
    }

    public function setProxyHost($host) {
        $this->proxyHost = $host;
        return $this;
    }

    public function getProxyHost() {
        return $this->proxyHost;
    }

    public function setProxyPort($port) {
        $this->proxyPort = $port;
        return $this;
    }

    public function getProxyPort() {
        return $this->proxyPort;
    }

    public function setProxyUser($user) {
        $this->proxyUser = $user;
        return $this;
    }

    public function getProxyUser() {
        return $this->proxyUser;
    }

    public function setProxyPassword($password) {
        $this->proxyPassword = $password;
        return $this;
    }

    public function getProxyPassword() {
        return $this->proxyPassword;
    }

    public function setConfigArchiveStatus($status) {
        $this->configArchiveStatus = $status;
        return $this;
    }

    public function getConfigArchiveStatus() {
        return $this->configArchiveStatus;
    }

    /**
     * Set updateInterval
     *
     * @param integer $updateInterval
     * @return Sensor
     */
    public function setUpdateInterval($updateInterval) {
        $this->updateInterval = $updateInterval;
        return $this;
    }

    /**
     * Get updateInterval
     *
     * @return integer
     */
    public function getUpdateInterval() {
        return $this->updateInterval;
    }

    /**
     * Set sensor firmware
     *
     * @return Sensor
     */
    public function setFirmware(\HoneySens\app\models\entities\Firmware $firmware = null) {
        $this->firmware = $firmware;
        return $this;
    }

    /**
     * Get sensor firmware
     *
     * @return \HoneySens\app\models\entities\Firmware
     */
    public function getFirmware() {
        return $this->firmware;
    }

    /**
     * Returns true if a custom firmware is set for this sensor.
     *
     * @return bool
     */
    public function hasFirmware() {
        return $this->firmware != null;
    }

    /**
     * Add a service assignment, meaning that this sensor is supposed to run the provided service.
     *
     * @param ServiceAssignment $service
     * @return $this
     */
    public function addService(ServiceAssignment $service) {
        $this->services[] = $service;
        $service->setSensor($this);
        return $this;
    }

    /**
     * Remove a service assignment, causing this sensor to stop running the given service.
     *
     * @param ServiceAssignment $service
     * @return $this
     */
    public function removeService(ServiceAssignment $service) {
        $this->services->removeElement($service);
        $service->setSensor(null);
        return $this;
    }

    /**
     * Get all service assignments associated with this sensor.
     *
     * @return ArrayCollection
     */
    public function getServices() {
        return $this->services;
    }

	public function getState() {
		$cert = $this->getCert() ? $this->getCert()->getId() : '';
		$crt_fp = $this->getCert() ? $this->getCert()->getFingerprint() : '';
		$last_status = $this->getLastStatus();
		$last_status_ts = $last_status ? $last_status->getTimestamp()->format('U') : '';
        $last_status_code = $last_status ? $last_status->getStatus() : null;
		$sw_version = $last_status ? $last_status->getSWVersion() : '';
		$last_ip = $last_status ? $last_status->getIP() : null;
		$firmware = $this->firmware ? $this->firmware->getId() : null;
        $services = array();
        foreach($this->services as $service) {
            $services[] = $service->getState();
        }
		return array(
			'id' => $this->getId(),
            'hostname' => $this->getHostname(),
			'name' => $this->getName(),
			'location' => $this->getLocation(),
			'division' => $this->getDivision()->getId(),
			'cert' => $cert,
			'crt_fp' => $crt_fp,
			'last_status' => $last_status_code,
			'last_status_ts' => $last_status_ts,
			'sw_version' => $sw_version,
			'last_ip' => $last_ip,
            'server_endpoint_mode' => $this->getServerEndpointMode(),
            'server_endpoint_host' => $this->getServerEndpointHost(),
            'server_endpoint_port_https' => $this->getServerEndpointPortHTTPS(),
            'network_ip_mode' => $this->getNetworkIPMode(),
            'network_ip_address' => $this->getNetworkIPAddress(),
            'network_ip_netmask' => $this->getNetworkIPNetmask(),
            'network_ip_gateway' => $this->getNetworkIPGateway(),
            'network_ip_dns' => $this->getNetworkIPDNS(),
            'network_mac_mode' => $this->getNetworkMACMode(),
            'network_mac_address' => $this->getNetworkMACAddress(),
            'proxy_mode' => $this->getProxyMode(),
            'proxy_host' => $this->getProxyHost(),
            'proxy_port' => $this->getProxyPort(),
            'proxy_user' => $this->getProxyUser(),
            'config_archive_status' => $this->getConfigArchiveStatus(),
            'update_interval' => $this->getUpdateInterval(),
            'firmware' => $firmware,
            'services' => $services
		);
	}
}