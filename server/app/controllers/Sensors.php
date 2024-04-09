<?php
namespace HoneySens\app\controllers;
use HoneySens\app\models\entities\Event;
use HoneySens\app\models\entities\LogEntry;
use HoneySens\app\models\entities\Sensor;
use HoneySens\app\models\entities\SensorStatus;
use HoneySens\app\models\entities\ServiceAssignment;
use HoneySens\app\models\entities\SSLCert;
use HoneySens\app\models\entities\Task;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\models\ServiceManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;

class Sensors extends RESTResource {

    static function registerRoutes($app, $em, $services, $config) {
        $app->get('/api/sensors[/{id:\d+}]', function(Request $request, Response $response, array $args) use ($app, $em, $services, $config) {
            $controller = new Sensors($em, $services, $config);
            $criteria = array('userID' => $controller->getSessionUserID(), 'id' => $args['id'] ?? null);
            try {
                $result = $controller->get($criteria);
            } catch(\Exception $e) {
                throw new NotFoundException();
            }
            $response->getBody()->write(json_encode($result));
            return $response;
       });

        $app->get('/api/sensors/config/{id:\d+}', function(Request $request, Response $response, array $args) use ($app, $em, $services, $config) {
            $controller = new Sensors($em, $services, $config);
            $task = $controller->requestConfigDownload($args['id']);
            $response->getBody()->write(json_encode($task->getState()));
            return $response;
        });

        $app->get('/api/sensors/status/by-sensor/{id:\d+}', function(Request $request, Response $response, array $args) use ($app, $em, $services, $config) {
            $controller = new Sensors($em, $services, $config);
            $criteria = array('userID' => $controller->getSessionUserID(), 'sensorID' => $args['id']);
            $result = $controller->getStatus($criteria);
            $response->getBody()->write(json_encode($result));
            return $response;
        });

        /**
         * This resource is used by authenticated sensors to receive firmware download details.
         * The return value is an array with platform names as keys and their respective access URIs as value.
         */
        $app->get('/api/sensors/firmware', function(Request $request, Response $response) use ($app, $em, $services, $config) {
            $controller = new Sensors($em, $services, $config);
            $sensor = $controller->validateSensorRequest('get', '');
            $body = json_encode($controller->getFirmwareURIs($sensor));
            $controller->setMACHeaders($sensor, 'get', $body);
            $response->getBody()->write($body);
            return $response;
        });

        $app->post('/api/sensors', function(Request $request, Response $response) use ($app, $em, $services, $config) {
            $controller = new Sensors($em, $services, $config);
            $sensor = $controller->create($request->getParsedBody());
            $result = $controller->getSensorState($sensor);
            $response->getBody()->write(json_encode($result));
            return $response;
        });

        /**
         * Polling endpoint for sensors to send status data and receive their current configuration.
         */
        $app->post('/api/sensors/status', function(Request $request, Response $response) use ($app, $em, $services, $config) {
            $controller = new Sensors($em, $services, $config);
            $requestBody = $request->getBody()->getContents();
            $sensor = $controller->validateSensorRequest('create', $requestBody);
            // Parse sensor request as JSON even if no correct Content-Type header is set
            $sensorData = $controller->poll($sensor, json_decode($requestBody, true));
            $body = json_encode($sensorData);
            $controller->setMACHeaders($sensor, 'create', $body);
            $response->getBody()->write($body);
            return $response;
        });

        $app->put('/api/sensors/{id:\d+}', function(Request $request, Response $response, array $args) use ($app, $em, $services, $config) {
            $controller = new Sensors($em, $services, $config);
            $sensor = $controller->update($args['id'], $request->getParsedBody());
            $result = $controller->getSensorState($sensor);
            $response->getBody()->write(json_encode($result));
            return $response;
        });

        $app->delete('/api/sensors/{id:\d+}', function(Request $request, Response $response, array $args) use ($app, $em, $services, $config) {
            $controller = new Sensors($em, $services, $config);
            $controller->delete($args['id'], $request->getParsedBody());
            $response->getBody()->write(json_encode([]));
            return $response;
        });
    }

    /**
     * Fetches sensors from the DB by various criteria:
     * - userID: return only sensors that belong to the user with the given id
     * - id: return the sensor with the given id
     * If no criteria are given, all sensors are returned.
     *
     * @param array $criteria
     * @return array
     * @throws ForbiddenException
     */
    public function get($criteria) {
        $this->assureAllowed('get');
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('s')->from('HoneySens\app\models\entities\Sensor', 's');
        if(V::key('userID', V::intType())->validate($criteria)) {
            $qb->join('s.division', 'd')
                ->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $criteria['userID']);
        }
        if(V::key('id', V::intVal())->validate($criteria)) {
            $qb->andWhere('s.id = :id')
                ->setParameter('id', $criteria['id']);
            return $this->getSensorState($qb->getQuery()->getSingleResult());
        } else {
            $sensors = array();
            foreach($qb->getQuery()->getResult() as $sensor) {
                $sensors[] = $this->getSensorState($sensor);
            }
            return $sensors;
        }
    }

    /**
     * Fetches sensor status data from the DB by various criteria:
     * - userID: return only status objects that belong to the user with the given id
     * - sensorID: return status objects that belong to the given sensor
     * If no criteria are given, all status objects are returned.
     *
     * @param array $criteria
     * @return array
     * @throws ForbiddenException
     */
    public function getStatus($criteria) {
        $this->assureAllowed('get');
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('ss')->from('HoneySens\app\models\entities\SensorStatus', 'ss')
            ->join('ss.sensor', 's');
        if(V::key('userID', V::intType())->validate($criteria)) {
            $qb->join('s.division', 'd')
                ->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $criteria['userID']);
        }
        if(V::key('sensorID', V::intVal())->validate($criteria)) {
            $qb->andWhere('s.id = :id')
                ->setParameter('id', $criteria['sensorID']);
        }
        $stati = array();
        foreach($qb->getQuery()->getResult() as $status) {
            $stati[] = $status->getState();
        }
        return $stati;
    }

    /**
     * Returns an associative array with firmware download URIs for all platforms.
     * If the given sensor overrides one of those with a specific revision, that one is returned here.
     *
     * @param Sensor $sensor
     * @return array
     */
    public function getFirmwareURIs(Sensor $sensor) {
        $platforms = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\Platform')->findAll();
        $result = array();
        foreach($platforms as $platform) {
            if($platform->hasDefaultFirmwareRevision()) {
                $result[$platform->getName()] = $platform->getFirmwareURI($platform->getDefaultFirmwareRevision());
            }
        }
        if($sensor->hasFirmware()) {
            $firmware = $sensor->getFirmware();
            $platform = $firmware->getPlatform();
            $result[$platform->getName()] = $platform->getFirmwareURI($firmware);
        }
        return $result;
    }

    /**
     * Creates and persists a new Sensor object.
     * The following parameters are required:
     * - name: Sensor name
     * - location: Informal sensor location description
     * - division: ID of the Division this sensor belongs to
     * - eapol_mode: 0 to 4, EAP over LAN authentication mode (or disabled)
     * - server_endpoint_mode: 0 or 1, how to contact the server
     * - network_ip_mode: 0 to 2, how an IP address is set on the sensor
     * - network_mac_mode: 0 or 1, use the default or a custom MAC address
     * - proxy_mode: 0 or 1, disable or enable HTTPS proxy support
     * - update_interval: null (use global default) or anything higher than 1 to specify the interval in minutes (max of 60)
     * - service_network: null (use global default) or a string such as '192.168.111.0/24' that is used for internal services
     * - firmware: null (use global defaults for any platform) or a valid id to force a specific firmware revision
     *
     * Depending on the previous attributes the following ones may also be required:
     * - server_endpoint_host: String that specifies the server name (IP or DNS name)
     * - server_endpoint_port_https: The TCP port the server uses for HTTPS
     * - network_ip_address: IP address in case of static network configuration
     * - network_ip_netmask: Netmask in case of static network configuration
     * - network_ip_gateway: Gateway in case of static network configuration (optional)
     * - network_ip_dns: DNS server to use in case of static network configuration (optional)
     * - network_mac_address: Custom MAC address
     * - network_dhcp_hostname: Desired hostname to send with DHCP requests (optional)
     * - proxy_host: Hostname / IP address of a HTTPS proxy to use
     * - proxy_port: The TCP port the proxy server listens on
     * - proxy_user: Required for proxy authentication
     * - proxy_password: Required for proxy authentication
     * - eapol_identity: Required for all EAPOL modes except when it's disabled
     * - eapol_password: Required for all EAPOL modes except TLS
     * - eapol_anon_identity: Required for some EAPOL configurations
     * - eapol_ca_cert: Server certificate for EAPOL, required for some configurations
     * - eapol_client_cert: Client certificate for EAPOL in TLS mode
     * - eapol_client_key: Client key for EAPOL in TLS mode
     * - eapol_client_key_password: Client key passphrase for EAPOL in TLS mode
     *
     * @param array $data
     * @return Sensor
     * @throws ForbiddenException
     */
    public function create($data) {
        $this->assureAllowed('create');
        // Validation
        V::arrayType()
            ->key('name', V::alnum('_-.')->length(1, 50))
            ->key('location', V::stringType()->length(0, 255))
            ->key('division', V::intVal())
            ->key('eapol_mode', V::intVal()->between(0, 4))
            ->key('server_endpoint_mode', V::intVal()->between(0, 1))
            ->key('network_ip_mode', V::intVal()->between(0, 2))
            ->key('network_mac_mode', V::intVal()->between(0, 1))
            ->key('proxy_mode', V::intVal()->between(0, 1))
            ->key('update_interval', V::optional(V::intVal()->between(1, 60)))
            ->key('service_network', V::optional(V::stringType()->length(9, 18)))
            ->key('firmware', V::optional(V::intVal()))
            ->check($data);
        $em = $this->getEntityManager();
        $division = $em->getRepository('HoneySens\app\models\entities\Division')->find($data['division']);
        V::objectType()->check($division);
        $this->assureUserAffiliation($division->getId());
        $firmware = null;
        if($data['firmware'] != null) {
            $firmware = $em->getRepository('HoneySens\app\models\entities\Firmware')->find($data['firmware']);
            V::objectType()->check($firmware);
        }
        // Persistence
        $sensor = new Sensor();
        $sensor->setName($data['name'])
            ->setLocation($data['location'])
            ->setDivision($division)
            ->setEAPOLMode($data['eapol_mode'])
            ->setServerEndpointMode($data['server_endpoint_mode'])
            ->setNetworkIPMode($data['network_ip_mode'])
            ->setNetworkMACMode($data['network_mac_mode'])
            ->setProxyMode($data['proxy_mode'])
            ->setUpdateInterval($data['update_interval'])
            ->setServiceNetwork($data['service_network'])
            ->setFirmware($firmware);
        // Validate and persist additional attributes depending on the previous ones
        if($sensor->getServerEndpointMode() == Sensor::SERVER_ENDPOINT_MODE_CUSTOM) {
            V::key('server_endpoint_host', V::stringType()->ip())
                ->key('server_endpoint_port_https', V::intVal()->between(1, 65535))
                ->check($data);
            $sensor->setServerEndpointHost($data['server_endpoint_host'])
                ->setServerEndpointPortHTTPS($data['server_endpoint_port_https']);
        }
        if($sensor->getNetworkIPMode() == Sensor::NETWORK_IP_MODE_STATIC) {
            V::key('network_ip_address', V::stringType()->ip())
                ->key('network_ip_netmask', V::stringType()->ip())
                ->key('network_ip_gateway', V::optional(V::stringType()->ip()))
                ->key('network_ip_dns', V::optional(V::stringType()->ip()))
                ->check($data);
            $sensor->setNetworkIPAddress($data['network_ip_address'])
                ->setNetworkIPNetmask($data['network_ip_netmask'])
                ->setNetworkIPGateway($data['network_ip_gateway'])
                ->setNetworkIPDNS($data['network_ip_dns']);
        } elseif ($sensor->getNetworkIPMode() == Sensor::NETWORK_IP_MODE_DHCP) {
            V::key('network_dhcp_hostname', V::optional(V::alnum('-.')->lowercase()->length(1, 253)))->check($data);
            $sensor->setNetworkDHCPHostname($data['network_dhcp_hostname'] == '' ? null : $data['network_dhcp_hostname']);
        }
        if($sensor->getNetworkMACMode() == Sensor::NETWORK_MAC_MODE_CUSTOM) {
            V::key('network_mac_address', V::stringType()->macAddress())
                ->check($data);
            $sensor->setNetworkMACAddress($data['network_mac_address']);
        }
        if($sensor->getProxyMode() == Sensor::PROXY_MODE_ENABLED) {
            V::key('proxy_host', V::stringType())
                ->key('proxy_port', V::intVal()->between(0, 65535))
                ->key('proxy_user', V::optional(V::stringType()))
                ->check($data);
            $sensor->setProxyHost($data['proxy_host'])
                ->setProxyPort($data['proxy_port']);
            if(strlen($data['proxy_user']) > 0) {
                $sensor->setProxyUser($data['proxy_user']);
                // Only set a password if one was provided by the client
                if(V::key('proxy_password', V::stringType())->validate($data)) {
                    $sensor->setProxyPassword($data['proxy_password']);
                }
            }
            else $sensor->setProxyUser(null);
        }
        if($sensor->getEAPOLMode() != Sensor::EAPOL_MODE_DISABLED) {
            V::key('eapol_identity', V::stringType()->length(1, 512))->check($data);
            $sensor->setEAPOLIdentity($data['eapol_identity']);
            if($sensor->getEAPOLMode() == Sensor::EAPOL_MODE_MD5) {
                V::key('eapol_password', V::optional(V::stringType()->length(1, 512)))->check($data);
                $sensor->setEAPOLPassword($data['eapol_password']);
            } else {
                // For the other modes, a CA cert can be specified
                V::key('eapol_ca_cert', V::optional(V::stringType()))->check($data);
                if($data['eapol_ca_cert'] != null) {
                    $cert = $this->verifyCertificate($data['eapol_ca_cert']);
                    $caCert = new SSLCert();
                    $caCert->setContent($cert);
                    $em->persist($caCert);
                    $sensor->setEAPOLCACert($caCert);
                };
                if($sensor->getEAPOLMode() == Sensor::EAPOL_MODE_TLS) {
                    V::key('eapol_client_cert', V::stringType())
                        ->key('eapol_client_key', V::stringType())
                        ->key('eapol_client_key_password', V::optional(V::stringType()->length(1, 512)))
                        ->check($data);
                    $cert = $this->verifyCertificate($data['eapol_client_cert']);
                    $key = $this->verifyKey($data['eapol_client_key'], $data['eapol_client_key_password'] == null ? '' : $data['eapol_client_key_password']);
                    $clientCert = new SSLCert();
                    $clientCert->setContent($cert)->setKey($key);
                    $em->persist($clientCert);
                    $sensor->setEAPOLClientCert($clientCert);
                    $sensor->setEAPOLClientCertPassphrase($data['eapol_client_key_password']);
                } else {
                    // PEAP or TTLS
                    V::key('eapol_password', V::optional(V::stringType()->length(1, 512)))
                        ->key('eapol_anon_identity', V::optional(V::stringType()->length(1, 512)))
                        ->check($data);
                    $sensor->setEAPOLPassword($data['eapol_password']);
                    $sensor->setEAPOLAnonymousIdentity($data['eapol_anon_identity']);
                }
            }
        }
        $em->persist($sensor);
        // TODO Config archive status is not necessary anymore
        $sensor->setConfigArchiveStatus(Sensor::CONFIG_ARCHIVE_STATUS_SCHEDULED);
        $em->flush();
        $this->log(sprintf('Sensor %s (ID %d) created', $sensor->getName(), $sensor->getId()), LogEntry::RESOURCE_SENSORS, $sensor->getId());
        return $sensor;
    }

    /**
     * Registers new status data from a sensor.
     * The given data object should have the following attributes:
     * - status: The actual status data as JSON object, encoded in base64
     *
     * The status data JSON object has to consist of the following attributes:
     * - timestamp: UNIX timestamp of the current sensor time
     * - status: Int that indicates the current sensor status (0 to 2)
     * - ip: IP address of the sensor's primary network interface
     * - free_mem: Free RAM on the sensor
     * - sw_version: Current sensor firmware revision
     *
     * TODO The following objects are only optional to preserve API compatibility with older sensors
     * The status data JSON object also MAY contain the following attributes:
     * - service_status: associative JSON array {service_name: service_status, ...}
     *
     * @param array $data
     * @return SensorStatus
     * @throws BadRequestException
     */
    public function createStatus($sensor, $data) {
        // Validation
        V::arrayType()
            ->key('status', V::stringType())
            ->check($data);
        $statusDataDecoded = base64_decode($data['status']);
        V::json()->check($statusDataDecoded);
        $statusData = json_decode($statusDataDecoded);
        V::objectType()
            ->attribute('timestamp', V::intVal())
            ->attribute('status', V::intVal()->between(0, 2))
            ->attribute('ip', V::stringType()->ip())
            ->attribute('free_mem', V::intVal())
            ->attribute('disk_usage', V::intVal())
            ->attribute('disk_total', V::intVal())
            ->attribute('sw_version', V::stringType())
            ->attribute('service_status', V::objectType()->each(V::intVal()->between(0,2), V::stringType()), false)
            ->check($statusData);
        // Check timestamp validity: only accept timestamps that aren't older than two minutes
        $now = new \DateTime();
        if(($sensor->getLastStatus() != null && $statusData->timestamp < $sensor->getLastStatus()->getTimestamp()->format('U'))
            || $statusData->timestamp < ($now->format('U') - 120)) {
            // TODO Invalid timestamp return value
            throw new BadRequestException();
        }
        // Persistence
        $status = new SensorStatus();
        $timestamp = new \DateTime('@' . $statusData->timestamp);
        $timestamp->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        // Set runningSince timestamp depending on previous sensor status
        $lastStatus = $sensor->getLastStatus();
        if($lastStatus != null && $lastStatus->getRunningSince() != null) {
            // Last status exists and wasn't a timeout: inherit its value
            $status->setRunningSince($lastStatus->getRunningSince());
        } else $status->setRunningSince($timestamp);
        $status->setTimestamp($timestamp)
            ->setStatus($statusData->status)
            ->setIP($statusData->ip)
            ->setFreeMem($statusData->free_mem)
            ->setDiskUsage($statusData->disk_usage)
            ->setDiskTotal($statusData->disk_total)
            ->setSWVersion($statusData->sw_version);
        $sensor->addStatus($status);
        if(property_exists($statusData, 'service_status')) $status->setServiceStatus($statusData->service_status);
        $em = $this->getEntityManager();
        $em->persist($status);
        $em->flush();
        return $status;
    }

    public function poll($sensor, $statusData) {
        $status = $this->createStatus($sensor, $statusData);
        $this->reduce($sensor, 10);
        // Collect sensor configuration and send it as response
        $sensorData = $this->getSensorState($sensor);
        if($status->getSensor()->getServerEndpointMode() == Sensor::SERVER_ENDPOINT_MODE_DEFAULT) {
            $sensorData['server_endpoint_host'] = $this->getConfig()['server']['host'];
            $sensorData['server_endpoint_port_https'] = $this->getConfig()['server']['portHTTPS'];
        }
        // Replace the update interval with the global default if no custom value was set for the sensor
        $sensorData['update_interval'] = $sensor->getUpdateInterval() != null ?
            $sensor->getUpdateInterval() : $this->getConfig()['sensors']['update_interval'];
        // Replace the service network with the global default if no custom value was set for the sensor
        $sensorData['service_network'] = $sensor->getServiceNetwork() != null ?
            $sensor->getServiceNetwork() : $this->getConfig()['sensors']['service_network'];
        // Replace service assignments with elaborate service data
        $services = array();
        $serviceRepository = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\Service');
        foreach($sensorData['services'] as $serviceAssignment) {
            $service = $serviceRepository->find($serviceAssignment['service']);
            $revisions = $service->getDistinctRevisions();
            // TODO getDefaultRevision() returns a string, $serviceAssignment['revision'] returns int IDs (so far unused)
            $targetRevision = $serviceAssignment['revision'] == null ? $service->getDefaultRevision() : $serviceAssignment['revision'];
            $serviceData = array();
            if(array_key_exists($targetRevision, $revisions)) {
                foreach($revisions[$targetRevision] as $arch => $r) {
                    $serviceData[$arch] = array(
                        'label' => $service->getLabel(),
                        'uri' => sprintf('%s:%s-%s', $service->getRepository(), $r->getArchitecture(), $r->getRevision()),
                        'rawNetworkAccess' => $r->getRawNetworkAccess(),
                        'catchAll' => $r->getCatchAll(),
                        'portAssignment' => $r->getPortAssignment()
                    );
                }
            }
            // Clients expect an associative array here.
            // StdClass instead of an empty associative array ensures a serialized '{}' instead of an '[]'.
            $services[$service->getId()] = count($serviceData) > 0 ? $serviceData : new \StdClass;
        }
        // Clients expect an associative array here.
        $sensorData['services'] = count($services) > 0 ? $services : new \StdClass;
        // Send passwords exclusively to the sensors (they aren't shown inside of the web interface)
        $sensorData['proxy_password'] = $status->getSensor()->getProxyPassword();
        $sensorData['eapol_password'] = $status->getSensor()->getEAPOLPassword();
        $sensorData['eapol_client_key_password'] = $status->getSensor()->getEAPOLClientCertPassphrase();
        // Attach firmware versioning information for all platforms
        $platformRepository = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\Platform');
        $firmware = array();
        foreach($platformRepository->findAll() as $platform) {
            if($platform->hasDefaultFirmwareRevision()) {
                $revision = $platform->getDefaultFirmwareRevision();
                $firmware[$platform->getName()] = array('revision' => $revision->getVersion(),
                    'uri' => $platform->getFirmwareURI($revision));
            }
        }
        // Sensor firmware overwrite
        if($sensor->hasFirmware()) {
            $f = $sensor->getFirmware();
            $firmware[$f->getPlatform()->getName()] = array('revision' => $f->getVersion(),
                'uri' => $f->getPlatform()->getFirmwareURI($f));
        }
        $sensorData['firmware'] = $firmware;
        // Unhandled event status data for physical LED indication
        $sensorData['unhandledEvents'] = $sensorData['new_events'] != 0;
        // If the server cert fingerprint was sent and differs from the current (or soon-to-be) TLS cert, include updated cert data
        $srvCert = $this->getServerCert();
        if(V::key('srv_crt_fp', V::stringType())->validate($statusData) && openssl_x509_fingerprint($srvCert, 'sha256') != $statusData['srv_crt_fp'])
            $sensorData['server_crt'] = $srvCert;
        // If the EAPOL CA cert fingerprint was sent and differs, include updated cert
        $caCertFP = $sensor->getEAPOLCACert() == null ? null : $sensor->getEAPOLCACert()->getFingerprint();
        if(V::key('eapol_ca_crt_fp', V::optional(V::stringType()))->validate($statusData) && $caCertFP != $statusData['eapol_ca_crt_fp'])
            $sensorData['eapol_ca_cert'] = $sensor->getEAPOLCACert() == null ? null : $sensor->getEAPOLCACert()->getContent();
        else unset($sensorData['eapol_ca_cert']);
        // If the EAPOL TLS cert fingerprint was sent and differs, include updated cert and key
        $clientCertFP = $sensor->getEAPOLClientCert() == null ? null : $sensor->getEAPOLClientCert()->getFingerprint();
        if(V::key('eapol_client_crt_fp', V::optional(V::stringType()))->validate($statusData) && $clientCertFP != $statusData['eapol_client_crt_fp']) {
            $sensorData['eapol_client_cert'] = $sensor->getEAPOLClientCert() == null ? null : $sensor->getEAPOLClientCert()->getContent();
            $sensorData['eapol_client_key'] = $sensor->getEAPOLClientCert() == null ? null : $sensor->getEAPOLClientCert()->getKey();
        } else unset($sensorData['eapol_client_cert']);
        return $sensorData;
    }

    /**
     * Updates an existing Sensor object.
     * The following parameters are required:
     * - name: Sensor name
     * - location: Informal sensor location description
     * - division: ID of the Division this sensor belongs to
     * - eapol_mode: 0 to 4, EAP over LAN authentication mode (or disabled)
     * - server_endpoint_mode: 0 or 1, how to contact the server
     * - network_ip_mode: 0 to 2, how an IP address is set on the sensor
     * - network_mac_mode: 0 or 1, use the default or a custom MAC address
     * - proxy_mode: 0 or 1, disable or enable HTTPS proxy support
     * - services: array of service assignments that are supposed to run on this sensor
     * - update_interval: null (use global default) or anything higher than 1 to specify the interval in minutes (max of 60)
     * - service_network: null (use global default) or a string such as '192.168.111.0/24' that is used for internal services
     * - firmware: null (use global defaults for any platform) or a valid id to force a specific firmware revision
     *
     * Depending on the previous attributes the following ones may also be required:
     * - server_endpoint_host: String that specifies the server name (IP or DNS name)
     * - server_endpoint_port_https: The TCP port the server uses for HTTPS
     * - network_ip_address: IP address in case of static network configuration
     * - network_ip_netmask: Netmask in case of static network configuration
     * - network_ip_gateway: Gateway in case of static network configuration (optional)
     * - network_ip_dNS: DNS server to use in case of static network configuration (optional)
     * - network_mac_address: Custom MAC address
     * - network_dhcp_hostname: Desired hostname to send with DHCP requests (optional)
     * - proxy_host: Hostname / IP address of a HTTPS proxy to use
     * - proxy_port: The TCP port the proxy server listens on
     * - proxy_user: Required for proxy authentication
     * - proxy_password: Required for proxy authentication
     * - eapol_identity: Required for all EAPOL modes except when it's disabled
     * - eapol_password: Required for all EAPOL modes except TLS
     * - eapol_anon_identity: Required for some EAPOL configurations
     * - eapol_ca_cert: Server certificate for EAPOL, required for some configurations
     * - eapol_client_cert: Client certificate for EAPOL in TLS mode
     * - eapol_client_key: Client key for EAPOL in TLS mode
     * - eapol_client_key_password: Client key passphrase for EAPOL in TLS mode
     *
     * @param int $id
     * @param array $data
     * @return Sensor
     * @throws ForbiddenException
     * @throws BadRequestException
     */
    public function update($id, $data) {
        $this->assureAllowed('update');
        // Validation
        V::intVal()->check($id);
        V::arrayType()
            ->key('name', V::alnum('_-.')->length(1, 50))
            ->key('location', V::stringType()->length(0, 255))
            ->key('division', V::intVal())
            ->key('eapol_mode', V::intVal()->between(0, 4))
            ->key('server_endpoint_mode', V::intVal()->between(0, 1))
            ->key('network_ip_mode', V::intVal()->between(0, 2))
            ->key('network_mac_mode', V::intVal()->between(0, 1))
            ->key('proxy_mode', V::intVal()->between(0, 1))
            ->key('update_interval', V::optional(V::intVal()->between(1, 60)))
            ->key('service_network', V::optional(V::regex('/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\/(?:30|2[0-9]|1[0-9]|[1-9]?)$/')))
            ->key('firmware', V::optional(V::intVal()))
            ->key('services', V::arrayVal()->each(V::arrayType()
                ->key('service', V::intVal())
                ->key('revision', V::nullType())  // The revision field is currently unused
            ))->check($data);
        $em = $this->getEntityManager();
        $sensor = $em->getRepository('HoneySens\app\models\entities\Sensor')->find($id);
        V::objectType()->check($sensor);
        $division = $em->getRepository('HoneySens\app\models\entities\Division')->find($data['division']);
        V::objectType()->check($division);
        $this->assureUserAffiliation($division->getId());
        // Persistence
        $sensor->setName($data['name']);
        $sensor->setLocation($data['location']);
        // TODO Move this sensor's events to the new Division, too
        $sensor->setDivision($division);
        $sensor->setServerEndpointMode($data['server_endpoint_mode']);
        if($sensor->getServerEndpointMode() == Sensor::SERVER_ENDPOINT_MODE_CUSTOM) {
            V::key('server_endpoint_host', V::stringType()->ip())
                ->key('server_endpoint_port_https', V::intVal()->between(1, 65535))
                ->check($data);
            $sensor->setServerEndpointHost($data['server_endpoint_host'])
                ->setServerEndpointPortHTTPS($data['server_endpoint_port_https']);
        } else {
            $sensor->setServerEndpointHost(null)
                ->setServerEndpointPortHTTPS(null);
        }
        $sensor->setNetworkIPMode($data['network_ip_mode']);
        if($sensor->getNetworkIPMode() == Sensor::NETWORK_IP_MODE_STATIC) {
            V::key('network_ip_address', V::stringType()->ip())
                ->key('network_ip_netmask', V::stringType()->ip())
                ->key('network_ip_gateway', V::optional(V::stringType()->ip()))
                ->key('network_ip_dns', V::optional(V::stringType()->ip()))
                ->check($data);
            $sensor->setNetworkIPAddress($data['network_ip_address'])
                ->setNetworkIPNetmask($data['network_ip_netmask'])
                ->setNetworkIPGateway($data['network_ip_gateway'])
                ->setNetworkIPDNS($data['network_ip_dns']);
        } else {
            $sensor->setNetworkIPAddress(null)
                ->setNetworkIPNetmask(null)
                ->setNetworkIPGateway(null)
                ->setNetworkIPDNS(null);
        }
        if($sensor->getNetworkIPMode() == Sensor::NETWORK_IP_MODE_DHCP) {
            V::key('network_dhcp_hostname', V::optional(V::alnum('-.')->lowercase()->length(1, 253)))->check($data);
            $sensor->setNetworkDHCPHostname($data['network_dhcp_hostname'] == '' ? null : $data['network_dhcp_hostname']);
        } else {
            $sensor->setNetworkDHCPHostname(null);
        }
        $sensor->setNetworkMACMode($data['network_mac_mode']);
        if($sensor->getNetworkMACMode() == Sensor::NETWORK_MAC_MODE_CUSTOM) {
            V::key('network_mac_address', V::stringType()->macAddress())
                ->check($data);
            $sensor->setNetworkMACAddress($data['network_mac_address']);
        } else {
            $sensor->setNetworkMACAddress(null);
        }
        $sensor->setProxyMode($data['proxy_mode']);
        if($sensor->getProxyMode() == Sensor::PROXY_MODE_ENABLED) {
            V::key('proxy_host', V::stringType())
                ->key('proxy_port', V::intVal()->between(0, 65535))
                ->key('proxy_user', V::optional(V::stringType()))
                ->check($data);
            $sensor->setProxyHost($data['proxy_host'])
                ->setProxyPort($data['proxy_port']);
            if(strlen($data['proxy_user']) > 0) {
                $sensor->setProxyUser($data['proxy_user']);
                // Only change the password if one was explicitly submitted
                if(V::key('proxy_password', V::stringType())->validate($data)) {
                    $sensor->setProxyPassword($data['proxy_password']);
                }
            } else {
                $sensor->setProxyUser(null);
                $sensor->setProxyPassword(null);
            }
        } else {
            $sensor->setProxyHost(null)
                ->setProxyPort(null)
                ->setProxyUser(null)
                ->setProxyPassword(null);
        }
        $sensor->setEAPOLMode($data['eapol_mode']);
        if($sensor->getEAPOLMode() != Sensor::EAPOL_MODE_DISABLED) {
            V::key('eapol_identity', V::stringType()->length(1, 512))->check($data);
            $sensor->setEAPOLIdentity($data['eapol_identity']);
            if($sensor->getEAPOLMode() == Sensor::EAPOL_MODE_MD5) {
                V::key('eapol_password', V::optional(V::stringType()->length(1, 512)), false)->check($data);
                // Only update the password if it was specified, otherwise keep the existing one
                if(V::key('eapol_password')->validate($data)) $sensor->setEAPOLPassword($data['eapol_password']);
                // Reset remaining parameters
                $sensor->setEAPOLClientCertPassphrase(null)
                    ->setEAPOLAnonymousIdentity(null);
                if($sensor->getEAPOLCACert() != null) {
                    $em->remove($sensor->getEAPOLCACert());
                    $sensor->setEAPOLCACert(null);
                }
                if($sensor->getEAPOLClientCert() != null) {
                    $em->remove($sensor->getEAPOLClientCert());
                    $sensor->setEAPOLClientCert(null);
                }
            } else {
                // For the other modes, a CA cert can be specified
                V::key('eapol_ca_cert', V::optional(V::stringType()), false)->check($data);
                // If a CA cert wasn't specified, just keep the existing one and do nothing
                if(V::key('eapol_ca_cert')->validate($data)) {
                    if($data['eapol_ca_cert'] == null) {
                        // Remove CA cert (if there was one set previously)
                        if($sensor->getEAPOLCACert() != null) {
                            $em->remove($sensor->getEAPOLCACert());
                            $sensor->setEAPOLCACert(null);
                        }
                    } else {
                        // Attribute was specified with a value: overwrite existing CA cert or create new one
                        $cert = $this->verifyCertificate($data['eapol_ca_cert']);
                        $caCert = $sensor->getEAPOLCACert();
                        if($caCert == null) {
                            $caCert = new SSLCert();
                            $em->persist($caCert);
                            $sensor->setEAPOLCACert($caCert);
                        }
                        $caCert->setContent($cert);
                    }
                }
                if($sensor->getEAPOLMode() == Sensor::EAPOL_MODE_TLS) {
                    V::key('eapol_client_cert', V::stringType(), false)
                        ->key('eapol_client_key', V::stringType(), false)
                        ->key('eapol_client_key_password', V::optional(V::stringType()->length(1, 512)), false)
                        ->check($data);
                    if(V::key('eapol_client_key_password')->validate($data)) $sensor->setEAPOLClientCertPassphrase($data['eapol_client_key_password']);
                    if(V::key('eapol_client_cert')->validate($data) && V::key('eapol_client_key')->validate($data)) {
                        // Attribute was specified with a value: overwrite existing client cert or create new one
                        $cert = $this->verifyCertificate($data['eapol_client_cert']);
                        $key = $this->verifyKey($data['eapol_client_key'], $sensor->getEAPOLClientCertPassphrase() == null ? '' : $sensor->getEAPOLClientCertPassphrase());
                        $clientCert = $sensor->getEAPOLClientCert();
                        if($clientCert == null) {
                            $clientCert = new SSLCert();
                            $em->persist($clientCert);
                            $sensor->setEAPOLClientCert($clientCert);
                        }
                        $clientCert->setContent($cert)->setKey($key);
                    } else {
                        // Check for existing cert
                        if($sensor->getEAPOLClientCert() == null) throw new BadRequestException();
                    }
                    // Reset unused parameters
                    $sensor->setEAPOLPassword(null)
                        ->setEAPOLAnonymousIdentity(null);
                } else {
                    // PEAP or TTLS
                    V::key('eapol_password', V::optional(V::stringType()->length(1, 512)), false)
                        ->key('eapol_anon_identity', V::optional(V::stringType()->length(1, 512)))
                        ->check($data);
                    // Keep the existing password if none was given
                    if(V::key('eapol_password')->validate($data)) $sensor->setEAPOLPassword($data['eapol_password']);
                    $sensor->setEAPOLAnonymousIdentity($data['eapol_anon_identity']);
                    // Reset unused parameters
                    if($sensor->getEAPOLClientCert() != null) {
                        $em->remove($sensor->getEAPOLClientCert());
                        $sensor->setEAPOLClientCert(null);
                    }
                    $sensor->setEAPOLClientCertPassphrase(null);
                }
            }
        } else {
            // EAPOL disabled, reset all other parameters
            $sensor->setEAPOLIdentity(null)
                ->setEAPOLPassword(null)
                ->setEAPOLClientCertPassphrase(null)
                ->setEAPOLAnonymousIdentity(null);
            if($sensor->getEAPOLCACert() != null) {
                $em->remove($sensor->getEAPOLCACert());
                $sensor->setEAPOLCACert(null);
            }
            if($sensor->getEAPOLClientCert() != null) {
                $em->remove($sensor->getEAPOLClientCert());
                $sensor->setEAPOLClientCert(null);
            }
        }
        $firmware = null;
        if($data['firmware'] != null) {
            $firmware = $em->getRepository('HoneySens\app\models\entities\Firmware')->find($data['firmware']);
            V::objectType()->check($firmware);
        }
        $sensor->setFirmware($firmware);
        $sensor->setUpdateInterval($data['update_interval']);
        $sensor->setServiceNetwork($data['service_network']);
        // Service handling, merge with existing data
        $serviceRepository = $em->getRepository('HoneySens\app\models\entities\Service');
        $revisionRepository = $em->getRepository('HoneySens\app\models\entities\ServiceRevision');
        // Clone the collection into an array so that newly added models won't interfere with the removal process
        $assignments = $sensor->getServices()->toArray();
        // Add/Update of service assignments
        $handledAssignments = array();
        foreach($data['services'] as $serviceAssignment) {
            $assigned = false;
            // Validate availability of the assignment
            $service = $serviceRepository->find($serviceAssignment['service']);
            V::objectType()->check($service);
            $revision = $serviceAssignment['revision'] == null ? null : $revisionRepository->find($serviceAssignment['revision']);
            // TODO Check if revision belongs to service
            // Update existing assignment
            foreach($assignments as $assignment) {
                if($assignment->getService()->getId() == $service->getId()) {
                    $assigned = true;
                    $handledAssignments[] = $assignment;
                    $assignment->setRevision($revision);
                }
            }
            // Add so far unassigned services
            if(!$assigned) {
                $newAssignment = new ServiceAssignment();
                $sensor->addService($newAssignment);
                $service->addAssignment($newAssignment);
                $newAssignment->setRevision($revision);
                $em->persist($newAssignment);
            }
        }
        // Deletion of remaining service assignments
        foreach(array_udiff($assignments, $handledAssignments, function($a, $b) {return strcmp(spl_object_hash($a), spl_object_hash($b));}) as $deletionCandidate) {
            $deletionCandidate->getSensor()->removeService($deletionCandidate);
            $deletionCandidate->getService()->removeAssignment($deletionCandidate);
            $deletionCandidate->setRevision(null);
            $em->remove($deletionCandidate);
        }
        $em->flush();
        $this->log(sprintf('Sensor %s (ID %d) updated', $sensor->getName(), $sensor->getId()), LogEntry::RESOURCE_SENSORS, $sensor->getId());
        return $sensor;
    }

    /**
     * Removes the sensor with the given id.
     * If 'archive' is set to true in additional criteria, all events of this sensor are sent to the archive first.
     *
     * @param int $id
     * @param array $criteria Additional deletion criteria
     * @throws ForbiddenException
     */
    public function delete($id, $criteria) {
        $this->assureAllowed('delete');
        // Validation
        try {
            // In case the current user can't delete events, force archiving
            $this->assureAllowed('delete', 'events');
            $archive = V::key('archive', V::boolType())->validate($criteria) && $criteria['archive'];
        } catch(\Exception $e) {
            $this->assureAllowed('archive', 'events');
            $archive = true;
        }
        V::intVal()->check($id);
        $em = $this->getEntityManager();
        $sensor = $em->getRepository('HoneySens\app\models\entities\Sensor')->find($id);
        V::objectType()->check($sensor);
        $this->assureUserAffiliation($sensor->getDivision()->getId());
        // (Archive and) Remove all events that belong to this sensor
        $events = $em->getRepository('HoneySens\app\models\entities\Event')->findBy(array('sensor' => $sensor));
        if($archive) {
            $eventIDs = array_map(function($e) { return $e->getId();}, $events);
            $eventController = new Events($em, $this->getServiceManager(), $this->getConfig());
            $eventController->archiveEvents($em, $eventIDs, true);
        } else foreach($events as $event) $em->remove($event);
        $sid = $sensor->getId();
        $em->remove($sensor);
        $em->flush();
        $this->log(sprintf('Sensor %s (ID %d) deleted', $sensor->getName(), $sid), LogEntry::RESOURCE_SENSORS, $sid);
    }

    /**
     * Triggers the creation and file download of a new sensor configuration archive.
     *
     * @param int $id Sensor id of the config archive that was requested
     * @return Task
     * @throws ForbiddenException
     */
    public function requestConfigDownload($id) {
        $this->assureAllowed('downloadConfig');
        // Validation
        V::intVal()->check($id);
        $sensor = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\Sensor')->find($id);
        V::objectType()->check($sensor);
        $this->assureUserAffiliation($sensor->getDivision()->getId());
        // Enqueue a new task and return it, it's the client's obligation to check that task's status and download the result
        $taskParams = $this->getSensorState($sensor);
        $taskParams['secret'] = $sensor->getSecret();
        // If this sensor doesn't have a custom service network defined, we rely on the system-wide configuration
        $taskParams['service_network'] = $sensor->getServiceNetwork() != null ? $sensor->getServiceNetwork() : $this->getConfig()['sensors']['service_network'];
        if($sensor->getServerEndpointMode() == Sensor::SERVER_ENDPOINT_MODE_DEFAULT) {
            $taskParams['server_endpoint_host'] = $this->getConfig()['server']['host'];
            $taskParams['server_endpoint_port_https'] = $this->getConfig()['server']['portHTTPS'];
        }
        $taskParams['server_endpoint_name'] = $this->getConfig()['server']['host'];
        $taskParams['proxy_password'] = $sensor->getProxyPassword();
        $taskParams['eapol_password'] = $sensor->getEAPOLPassword();
        $taskParams['eapol_client_key_password'] = $sensor->getEAPOLClientCertPassphrase();
        if($sensor->getEAPOLCACert() != null) $taskParams['eapol_ca_cert'] = $sensor->getEAPOLCACert()->getContent();
        if($sensor->getEAPOLClientCert() != null) {
            $taskParams['eapol_client_cert'] = $sensor->getEAPOLClientCert()->getContent();
            $taskParams['eapol_client_key'] = $sensor->getEAPOLClientCert()->getKey();
        } else $taskParams['eapol_client_key'] = null;
        $task = $this->getServiceManager()->get(ServiceManager::SERVICE_TASK)->enqueue($this->getSessionUser(), Task::TYPE_SENSORCFG_CREATOR, $taskParams);
        return $task;
    }

    /**
     * Removes the oldest status entries of a particular sensor
     *
     * @param Sensor $sensor The sensor to clean up for
     * @param int $keep The number of entries to keep
     */
    public function reduce($sensor, $keep) {
        // Validation
        V::intVal()->check($keep);
        // Persistence
        $em = $this->getEntityManager();
        $statusSorted = array();
        $allStatus = $sensor->getStatus();
        foreach($allStatus as $key => $status) {
            $statusSorted[$key] = $status;
            $timestamps[$key] = $status->getTimestamp();
        }
        if(count($statusSorted) > $keep) {
            array_multisort($timestamps, SORT_DESC, $statusSorted);
            $toRemove = array_slice($statusSorted, $keep);
            foreach($toRemove as $status) {
                $sensor->removeStatus($status);
                $em->remove($status);
            }
            $em->flush();
        }
    }

    /**
     * Verifies and returns an X.509 certificate.
     *
     * @param string $data
     * @return string
     * @throws BadRequestException
     */
    private function verifyCertificate($data) {
        try {
            $decoded = base64_decode($data);
            if ($decoded) {
                $cert = openssl_x509_read($decoded);
                if ($cert) return $decoded;
            }
        } catch(\Exception $e) {
            throw new BadRequestException();
        }
        throw new BadRequestException();
    }

    /**
     * Verifies and returns an X.509 private key.
     *
     * @param $data
     * @param $passphrase
     * @return bool|string
     * @throws BadRequestException
     */
    private function verifyKey($data, $passphrase) {
        try {
            $decoded = base64_decode($data);
            if ($decoded) {
                $key = openssl_pkey_get_private($decoded, $passphrase);
                if ($key) return $decoded;
            }
        } catch(\Exception $e) {
            throw new BadRequestException();
        }
        throw new BadRequestException();
    }

    /**
     * Enriches sensor state with data acquired from external sources (such as new event count) and returns it.
     *
     * @param Sensor $sensor
     * @return array
     */
    private function getSensorState(Sensor $sensor) {
        $state = $sensor->getState();
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('count(e.id)')
            ->from('HoneySens\app\models\entities\Event', 'e')
            ->where('e.sensor = :sensor AND e.status = :status')
            ->setParameters(array('sensor' => $sensor, 'status' => Event::STATUS_UNEDITED));
        $state['new_events'] = intval($qb->getQuery()->getSingleScalarResult());
        return $state;
    }
}
