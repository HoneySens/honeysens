<?php
namespace HoneySens\app\services\dto;

use HoneySens\app\models\constants\SensorEAPOLMode;
use HoneySens\app\models\constants\SensorNetworkIPMode;
use HoneySens\app\models\constants\SensorNetworkMACMode;
use HoneySens\app\models\constants\SensorProxyMode;
use HoneySens\app\models\constants\SensorServerEndpointMode;

class SensorParams {
    // Sensor name
    public string $name;
    // Informal sensor location description
    public string $location;
    // ID of the Division this sensor belongs to
    public int $divisionID;

    // EAP over LAN authentication mode
    public SensorEAPOLMode $eapolMode;
    // Required for all EAPOL modes except when it's disabled
    public ?string $eapolIdentity = null;
    // Required for all EAPOL modes except TLS. If not set on update, the existing password is kept.
    public ?string $eapolPassword;
    // Required for some EAPOL configurations
    public ?string $eapolAnonIdentity = null;
    // Server certificate for EAPOL, required for some configurations. If not set on update, the existing cert is kept.
    public ?string $eapolCACert;
    // Client certificate for EAPOL in TLS mode. If not set on update, the existing password is kept.
    public ?string $eapolClientCert;
    // Client key for EAPOL in TLS mode. If not set on update, the existing password is kept.
    public ?string $eapolClientKey;
    // Client key passphrase for EAPOL in TLS mode. If not set on update, the existing password is kept.
    public ?string $eapolClientKeyPassword;

    // Whether to use the global or a sensor-specific custom server endpoint
    public SensorServerEndpointMode $serverEndpointMode;
    // CUSTOM: Server name (IP or DNS name) to connect to
    public ?string $serverEndpointHost = null;
    // CUSTOM: TCP port to contact the server on
    public ?int $serverEndpointPort = null;

    // How the sensor receives its IP address
    public SensorNetworkIPMode $ipMode;
    // DHCP: Desired hostname to send with DHCP requests
    public ?string $dhcpHostname = null;
    // STATIC: Sensor IP address
    public ?string $ipAddress = null;
    // STATIC: Sensor netmask
    public ?string $ipNetmask = null;
    // STATIC: Sensor gateway
    public ?string $ipGateway = null;
    // STATIC: Sensor DNS server
    public ?string $ipDNS = null;

    // Whether to use the default or a custom MAC address
    public SensorNetworkMACMode $macMode;
    // CUSTOM: Sensor MAC address
    public ?string $macAddress = null;

    // Whether to use a proxy when connecting to the server
    public SensorProxyMode $proxyMode;
    // ENABLED: Hostname / IP address of the HTTPS proxy
    public ?string $proxyHost = null;
    // ENABLED: TCP port the proxy server listens on
    public ?int $proxyPort = null;
    // ENABLED: Proxy authentication user
    public ?string $proxyUser = null;
    // ENABLED: Proxy authentication password. If not set on update, the existing password is kept.
    public ?string $proxyPassword;

    //  Server contact interval in minutes, set to null to use the global default value
    public ?int $updateInterval = null;
    // Internal service network, set to null to use global defaults or a CIDR notation such as '192.168.111.0/24'
    public ?string $serviceNetwork = null;
    // Desired firmware revision, set to null to use global defaults (for any platform) or a valid ID to force a specific firmware revision
    public ?int $firmwareID = null;

    // Services assignments that are supposed to run on this sensor as [['service' => $id, 'revision' => null], ...]
    public ?array $services = null;
}
