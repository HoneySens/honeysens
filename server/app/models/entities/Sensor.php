<?php
namespace HoneySens\app\models\entities;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\GeneratedValue;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\Mapping\Table;
use HoneySens\app\models\constants\SensorEAPOLMode;
use HoneySens\app\models\constants\SensorNetworkIPMode;
use HoneySens\app\models\constants\SensorNetworkMACMode;
use HoneySens\app\models\constants\SensorProxyMode;
use HoneySens\app\models\constants\SensorServerEndpointMode;

/**
 * A sensor is a virtual or physical device that is remotely
 * managed by the HoneySens server and deployment target for
 * honeypot services. These services report security-related
 * events back to the server.
 */
#[Entity]
#[Table(name: "sensors")]
class Sensor {

    #[Id]
    #[Column(type: Types::INTEGER)]
    #[GeneratedValue]
    private int $id;

    /**
     * Display name of this sensor.
     */
    #[Column(type: Types::STRING)]
    public string $name;

    /**
     * Sensor description, shown in frontend and intended to be used as
     * location field so that admins don't forget where their physical
     * sensors are located (e.g. via building and room numbers).
     */
    #[Column(type: Types::STRING)]
    public string $location;

    /**
     * Secret key used to authenticate with the server.
     */
    #[Column(type: Types::STRING)]
    public string $secret;

    /**
     * Sensor system state snapshots to track sensor health.
     */
    #[OneToMany(mappedBy: "sensor", targetEntity: SensorStatus::class, cascade: ["remove"])]
    private Collection $status;

    /**
     * The division this sensor is associated with.
     */
    #[ManyToOne(targetEntity: Division::class, inversedBy: "sensors")]
    public Division $division;

    /**
     * Whether this sensor contacts the server via the global default configuration
     * or individual sensor-specific settings ($server*).
     */
    #[Column()]
    public SensorServerEndpointMode $serverEndpointMode;

    /**
     * Server hostname this sensor should use to connect to the server.
     * Can be either a DNS-resolvable domain name or an IP address.
     * Only evaluated if $serverEndpointMode is set to CUSTOM.
     */
    #[Column(type: Types::STRING, nullable: true)]
    public ?string $serverEndpointHost = null;

    /**
     * The TCP port the sensor should to connect to the server over HTTPS.
     * Only evaluated if $serverEndpointMode is set to CUSTOM.
     */
    #[Column(type: Types::INTEGER, nullable: true)]
    public ?int $serverEndpointPortHTTPS = null;

    /**
     * Determines how the sensor receives the IP address for its primary
     * network interface.
     */
    #[Column()]
    public SensorNetworkIPMode $networkIPMode;

    /**
     * IP address of the primary sensor network interface.
     * Only evaluated if $networkIPMode is set to STATIC.
     */
    #[Column(type: Types::STRING, nullable: true)]
    public ?string $networkIPAddress = null;

    /**
     * Netmask of the primary sensor network interface.
     * Only evaluated if $networkIPMode is set to STATIC.
     */
    #[Column(type: Types::STRING, nullable: true)]
    public ?string $networkIPNetmask = null;

    /**
     * Gateway of the primary sensor network interface.
     * Only evaluated if $networkIPMode is set to STATIC.
     */
    #[Column(type: Types::STRING, nullable: true)]
    public ?string $networkIPGateway = null;

    /**
     * DNS server for the primary sensor network interface.
     * Only evaluated if $networkIPMode is set to STATIC.
     */
    #[Column(type: Types::STRING, nullable: true)]
    public ?string $networkIPDNS = null;

    /**
     * Whether to use the built-in original MAC address
     * for the primary sensor network interface or a custom one.
     */
    #[Column()]
    public SensorNetworkMACMode $networkMACMode;

    /**
     * Custom MAC address for the primary sensor network interface.
     * Only evaluated if $networkMACMode is set to CUSTOM.
     */
    #[Column(type: Types::STRING, nullable: true)]
    public ?string $networkMACAddress = null;

    /**
     * Optional desired hostname to include within DHCP requests.
     * If null, no hostname is sent to the DHCP server.
     * Only evaluated if $networkIPMode is set to DHCP.
     */
    #[Column(type: Types::STRING, nullable: true)]
    public ?string $networkDHCPHostname = null;

    /**
     * Whether the sensor should use a proxy to connect to its server.
     */
    #[Column()]
    public SensorProxyMode $proxyMode;

    /**
     * Proxy server host address or name.
     * Only evaluated if $proxyMode is ENABLED.
     */
    #[Column(type: Types::STRING, nullable: true)]
    public ?string $proxyHost = null;

    /**
     * Proxy server TCP port.
     * Only evaluated if $proxyMode is ENABLED.
     */
    #[Column(type: Types::INTEGER, nullable: true)]
    public ?int $proxyPort = null;

    /**
     * User to authenticate as with the proxy server.
     * Only evaluated if $proxyMode is ENABLED.
     */
    #[Column(type: Types::STRING, nullable: true)]
    public ?string $proxyUser = null;

    /**
     * Password to authenticate with the proxy server.
     * Only evaluated if $proxyMode is ENABLED.
     */
    #[Column(type: Types::STRING, nullable: true)]
    public ?string $proxyPassword = null;

    /**
     * Custom sensor state update interval in minutes.
     */
    #[Column(type: Types::INTEGER, nullable: true)]
    public ?int $updateInterval = null;

    /**
     * If set, designates a custom firmware revision to deploy to this sensor.
     * Otherwise, the sensor uses the global default firmware.
     */
    #[ManyToOne(targetEntity: Firmware::class)]
    public ?Firmware $firmware = null;

    /**
     * A collection of services that are configured to run on this sensor.
     */
    #[OneToMany(mappedBy: "sensor", targetEntity: ServiceAssignment::class, cascade: ["remove"])]
    private Collection $services;

    /**
     * If set, the custom internal "service" network range to use on this sensor.
     * Otherwise, the sensor uses the global default service network range.
     */
    #[Column(type: Types::STRING, nullable: true)]
    public ?string $serviceNetwork = null;

    /**
     * Whether the sensor should use EAPOL to authenticate on its local network.
     */
    #[Column()]
    public SensorEAPOLMode $EAPOLMode = SensorEAPOLMode::DISABLED;

    /**
     * Identity to use for EAPOL authentication.
     * Only evaluated if $EAPOLMode is not DISABLED.
     */
    #[Column(type: Types::STRING, nullable: true)]
    public ?string $EAPOLIdentity = null;

    /**
     * Password used for EAPOL authentication.
     * Only required for certain EAPOL modes.
     */
    #[Column(type: Types::STRING, nullable: true)]
    public ?string $EAPOLPassword = null;

    /**
     * Anonymous identity used during EAPOL authentication.
     * Optional and only evaluated if $EAPOLMode is not DISABLED..
     */
    #[Column(type: Types::STRING, nullable: true)]
    public ?string $EAPOLAnonymousIdentity = null;

    /**
     * CA certificate used for EAPOL TLS authentication.
     * Only evaluated if $EAPOLMode is TLS or TTLS.
     */
    #[OneToOne(targetEntity: SSLCert::class, cascade: ["remove"])]
    public ?SSLCert $EAPOLCACert = null;

    /**
     * Client certificate used for EAPOL TLS authentication.
     * Only evaluated if $EAPOLMode is TLS or TTLS.
     */
    #[OneToOne(targetEntity: SSLCert::class, cascade: ["remove"])]
    public ?SSLCert $EAPOLClientCert = null;

    /**
     * Passphrase for the EAPOL client key, if any.
     * Only evaluated if $EAPOLMode is TLS or TTLS.
     */
    #[Column(type: Types::STRING, nullable: true)]
    public ?string $EAPOLClientCertPassphrase = null;

    public function __construct() {
        $this->secret = $this->generateSecret();
        $this->status = new ArrayCollection();
        $this->services = new ArrayCollection();
    }

    public function getId(): int {
        return $this->id;
    }

    /**
     * Sensors use their id as hostname, preceded by an string, to conform to host and domain name conventions
     */
    public function getHostname(): string {
        return 's' . $this->id;
    }

    /**
     * Adds a system state snapshot to this sensor.
     */
    public function addStatus(SensorStatus $status): void {
        $this->status[] = $status;
        $status->sensor = $this;
    }

    /**
     * Removes a system state snapshot from this sensor.
     */
    public function removeStatus(SensorStatus $status): void {
        $this->status->removeElement($status);
    }

    /**
     * Returns all stored system state snapshots of this sensor.
     */
    public function getStatus(): Collection {
        return $this->status;
    }

    /**
     * Returns the last known system state snapshot for this sensor
     * of null in case no snapshots are available.
     */
    public function getLastStatus(): ?SensorStatus {
        $statusSorted = array();
        foreach($this->status as $key => $status) {
            $statusSorted[$key] = $status;
            $timestamps[$key] = $status->timestamp;
        }
        if(count($statusSorted) > 0) {
            array_multisort($timestamps, SORT_DESC, $statusSorted);
            return $statusSorted[0];
        } else return null;
    }

    /**
     * Adds a service assignment, meaning that this sensor is supposed to run the provided service.
     */
    public function addService(ServiceAssignment $service): void {
        $this->services[] = $service;
        $service->sensor = $this;
    }

    /**
     * Removes a service assignment, causing this sensor to stop running a service.
     */
    public function removeService(ServiceAssignment $service): void {
        $this->services->removeElement($service);
    }

    /**
     * Get all service assignments associated with this sensor.
     */
    public function getServices(): Collection {
        return $this->services;
    }

    public function getState(): array {
        $eapol_ca_cert = $this->EAPOLCACert?->getFingerprint();
        $eapol_client_cert = $this->EAPOLClientCert?->getFingerprint();
        $last_status = $this->getLastStatus();
        $last_status_ts = $last_status?->timestamp->format('U');
        $last_status_code = $last_status?->status;
        $last_status_since = $last_status?->runningSince?->format('U');
        $last_service_status = $last_status?->getServiceStatus();
        $sw_version = $last_status ? $last_status->swVersion : '';
        $last_ip = $last_status?->ip;
        $firmware = $this->firmware?->getId();
        $services = array();
        foreach($this->services as $service) {
            $services[] = $service->getState();
        }
        return array(
            'id' => $this->id ?? null,
            'hostname' => $this->getHostname(),
            'name' => $this->name,
            'location' => $this->location,
            'division' => $this->division->getId(),
            'eapol_mode' => $this->EAPOLMode->value,
            'eapol_identity' => $this->EAPOLIdentity,
            'eapol_anon_identity' => $this->EAPOLAnonymousIdentity,
            'eapol_ca_cert' => $eapol_ca_cert,
            'eapol_client_cert' => $eapol_client_cert,
            'last_status' => $last_status_code,
            'last_status_ts' => $last_status_ts,
            'last_status_since' => $last_status_since,
            'last_service_status' => $last_service_status,
            'sw_version' => $sw_version,
            'last_ip' => $last_ip,
            'server_endpoint_mode' => $this->serverEndpointMode->value,
            'server_endpoint_host' => $this->serverEndpointHost,
            'server_endpoint_port_https' => $this->serverEndpointPortHTTPS,
            'network_ip_mode' => $this->networkIPMode->value,
            'network_ip_address' => $this->networkIPAddress,
            'network_ip_netmask' => $this->networkIPNetmask,
            'network_ip_gateway' => $this->networkIPGateway,
            'network_ip_dns' => $this->networkIPDNS,
            'network_mac_mode' => $this->networkMACMode->value,
            'network_mac_address' => $this->networkMACAddress,
            'network_dhcp_hostname' => $this->networkDHCPHostname,
            'proxy_mode' => $this->proxyMode->value,
            'proxy_host' => $this->proxyHost,
            'proxy_port' => $this->proxyPort,
            'proxy_user' => $this->proxyUser,
            'update_interval' => $this->updateInterval,
            'firmware' => $firmware,
            'services' => $services,
            'service_network' => $this->serviceNetwork
        );
    }

    private function generateSecret(): string {
        return bin2hex(openssl_random_pseudo_bytes(32));
    }
}
