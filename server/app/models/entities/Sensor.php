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

    const EAPOL_MODE_DISABLED = 0;
    const EAPOL_MODE_MD5 = 1;
    const EAPOL_MODE_TLS = 2;
    const EAPOL_MODE_PEAP = 3;
    const EAPOL_MODE_TTLS = 4;

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
     * @Column(type="string")
     */
    protected $secret;

    /**
     * @OneToMany(targetEntity="HoneySens\app\models\entities\SensorStatus", mappedBy="sensor", cascade={"remove"})
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
     * Optional desired hostname to include within DHCP requests.
     * If null, no hostname is sent to the DHCP server.
     *
     * @Column(type="string", nullable=true)
     */
    protected $networkDHCPHostname;

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
     * Custom update interval in minutes.
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
     * @OneToMany(targetEntity="HoneySens\app\models\entities\ServiceAssignment", mappedBy="sensor", cascade={"remove"})
     */
    protected $services;

    /**
     * Custom service network to use on that sensor.
     *
     * @Column(type="string", nullable=true)
     */
    protected $serviceNetwork = null;

    /**
     * Sensor authentication status/mode.
     *
     * @Column(type="integer")
     */
    protected $EAPOLMode = 0;

    /**
     * Identity used for EAPOL authentication.
     *
     * @Column(type="string", nullable=true)
     */
    protected $EAPOLIdentity = null;

    /**
     * Password used for EAPOL authentication.
     * Only required for certain EAPOL modes.
     *
     * @Column(type="string", nullable=true)
     */
    protected $EAPOLPassword = null;

    /**
     * Anonymous identity used during EAPOL authentication.
     * Optional.
     *
     * @Column(type="string", nullable=true)
     */
    protected $EAPOLAnonymousIdentity = null;

    /**
     * CA certificate used for EAPOL TLS authentication.
     *
     * @OneToOne(targetEntity="HoneySens\app\models\entities\SSLCert", cascade={"remove"})
     */
    protected $EAPOLCACert;

    /**
     * Client certificate used for EAPOL TLS authentication.
     *
     * @OneToOne(targetEntity="HoneySens\app\models\entities\SSLCert", cascade={"remove"})
     */
    protected $EAPOLClientCert;

    /**
     * Passphrase for the client key, if any.
     *
     * @Column(type="string", nullable=true)
     */
    protected $EAPOLClientCertPassphrase;

    public function __construct() {
        $this->secret = $this->generateSecret();
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
     * Set secret/key
     *
     * @param string $secret
     * @return Sensor
     */
    public function setSecret($secret) {
        $this->secret = $secret;
        return $this;
    }

    /**
     * Get secret/key
     *
     * @return string
     */
    public function getSecret() {
        return $this->secret;
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

    public function setNetworkDHCPHostname($hostname) {
        $this->networkDHCPHostname = $hostname;
        return $this;
    }

    public function getNetworkDHCPHostname() {
        return $this->networkDHCPHostname;
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
     * @return integer|null
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

    /**
     * Set serviceNetwork
     *
     * @param string $serviceNetwork
     * @return Sensor
     */
    public function setServiceNetwork($serviceNetwork) {
        $this->serviceNetwork = $serviceNetwork;
        return $this;
    }

    /**
     * Get serviceNetwork
     *
     * @return string|null
     */
    public function getServiceNetwork() {
        return $this->serviceNetwork;
    }

    /**
     * Set the EAPOL mode
     *
     * @param integer $mode
     * @return $this
     */
    public function setEAPOLMode($mode) {
        $this->EAPOLMode = $mode;
        return $this;
    }

    /**
     * Get the current EAPOL mode
     *
     * @return integer
     */
    public function getEAPOLMode() {
        return $this->EAPOLMode;
    }

    /**
     * Set the identity string used with EAPOL
     *
     * @param string $identity
     * @return $this
     */
    public function setEAPOLIdentity($identity) {
        $this->EAPOLIdentity = $identity;
        return $this;
    }

    /**
     * Get the identity used for EAPOL
     *
     * @return string|null
     */
    public function getEAPOLIdentity() {
        return $this->EAPOLIdentity;
    }

    /**
     * Set the password string used with EAPOL
     *
     * @param string $password
     * @return $this
     */
    public function setEAPOLPassword($password) {
        $this->EAPOLPassword = $password;
        return $this;
    }

    /**
     * Get the password used for EAPOL
     *
     * @return string|null
     */
    public function getEAPOLPassword() {
        return $this->EAPOLPassword;
    }

    /**
     * Set the anonymous identity string used with EAPOL
     *
     * @param string $identity
     * @return $this
     */
    public function setEAPOLAnonymousIdentity($identity) {
        $this->EAPOLAnonymousIdentity = $identity;
        return $this;
    }

    /**
     * Get the anonymous identity used for EAPOL
     *
     * @return string|null
     */
    public function getEAPOLAnonymousIdentity() {
        return $this->EAPOLAnonymousIdentity;
    }

    /**
     * Set the CA certificate used for EAPOL
     *
     * @param SSLCert|null $cert
     * @return $this
     */
    public function setEAPOLCACert(SSLCert $cert = null) {
        $this->EAPOLCACert = $cert;
        return $this;
    }

    /**
     * Get the CA certificate used for EAPOL
     *
     * @return SSLCert|null
     */
    public function getEAPOLCACert() {
        return $this->EAPOLCACert;
    }

    /**
     * Set the client certificate used for EAPOL
     *
     * @param SSLCert|null $cert
     * @return $this
     */
    public function setEAPOLClientCert(SSLCert $cert = null) {
        $this->EAPOLClientCert = $cert;
        return $this;
    }

    /**
     * Get the client certificate used for EAPOL
     *
     * @return SSLCert|null
     */
    public function getEAPOLClientCert() {
        return $this->EAPOLClientCert;
    }

    /**
     * Set the passphrase for the client key
     *
     * @param string|null $passphrase
     * @return $this
     */
    public function setEAPOLClientCertPassphrase($passphrase) {
        $this->EAPOLClientCertPassphrase = $passphrase;
        return $this;
    }

    /**
     * Get the passphrase for the client key
     *
     * @return string|null
     */
    public function getEAPOLClientCertPassphrase() {
        return $this->EAPOLClientCertPassphrase;
    }

    public function getState() {
        $eapol_password = $this->getEAPOLPassword() ? '******' : null;
        $eapol_ca_cert = $this->getEAPOLCACert() ? $this->getEAPOLCACert()->getFingerprint() : null;
        $eapol_client_cert = $this->getEAPOLClientCert() ? $this->getEAPOLClientCert()->getFingerprint() : null;
        $eapol_client_key_password = $this->getEAPOLClientCertPassphrase() ? '******' : null;
        $last_status = $this->getLastStatus();
        $last_status_ts = $last_status ? $last_status->getTimestamp()->format('U') : null;
        $last_status_code = $last_status ? $last_status->getStatus() : null;
        $last_status_since = $last_status ? $last_status->getRunningSince() ? $last_status->getRunningSince()->format('U') : null : null;
        $last_service_status = $last_status ? $last_status->getServiceStatus() : null;
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
            'eapol_mode' => $this->getEAPOLMode(),
            'eapol_identity' => $this->getEAPOLIdentity(),
            'eapol_password' => $eapol_password,
            'eapol_anon_identity' => $this->getEAPOLAnonymousIdentity(),
            'eapol_ca_cert' => $eapol_ca_cert,
            'eapol_client_cert' => $eapol_client_cert,
            'eapol_client_key_password' => $eapol_client_key_password,
            'last_status' => $last_status_code,
            'last_status_ts' => $last_status_ts,
            'last_status_since' => $last_status_since,
            'last_service_status' => $last_service_status,
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
            'network_dhcp_hostname' => $this->getNetworkDHCPHostname(),
            'proxy_mode' => $this->getProxyMode(),
            'proxy_host' => $this->getProxyHost(),
            'proxy_port' => $this->getProxyPort(),
            'proxy_user' => $this->getProxyUser(),
            'config_archive_status' => $this->getConfigArchiveStatus(),
            'update_interval' => $this->getUpdateInterval(),
            'firmware' => $firmware,
            'services' => $services,
            'service_network' => $this->getServiceNetwork()
        );
    }

    private function generateSecret() {
        return bin2hex(openssl_random_pseudo_bytes(32));
    }
}