<?php
namespace HoneySens\app\controllers;
use HoneySens\app\models\entities\Sensor;
use HoneySens\app\models\entities\SensorStatus;
use HoneySens\app\models\entities\ServiceAssignment;
use HoneySens\app\models\entities\SSLCert;
use HoneySens\app\models\entities\Task;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\models\ServiceManager;
use phpseclib\File\X509;
use Respect\Validation\Validator as V;

class Sensors extends RESTResource {

    static function registerRoutes($app, $em, $services, $config, $messages) {
        $app->get('/api/sensors(/:id)/', function($id = null) use ($app, $em, $services, $config, $messages) {
            $controller = new Sensors($em, $services, $config);
            $criteria = array();
            $criteria['userID'] = $controller->getSessionUserID();
            $criteria['id'] = $id;
            try {
                $result = $controller->get($criteria);
            } catch(\Exception $e) {
                throw new NotFoundException();
            }
            echo json_encode($result);
       });

        $app->get('/api/sensors/config/:id', function($id) use ($app, $em, $services, $config, $messages) {
            $controller = new Sensors($em, $services, $config);
            echo json_encode($controller->requestConfigDownload($id));
        });

        $app->get('/api/sensors/status/by-sensor/:id', function($id) use ($app, $em, $services, $config, $messages) {
            $controller = new Sensors($em, $services, $config);
            $criteria = array();
            $criteria['userID'] = $controller->getSessionUserID();
            $criteria['sensorID'] = $id;
            try {
                $result = $controller->getStatus($criteria);
            } catch(\Exception $e) {
                throw new NotFoundException();
            }
            echo json_encode($result);
        });

        /**
         * This resource is used by sensors to receive firmware download details.
         * Sensors are authenticated with their respective client certificates.
         * The return value is an array with platform names as keys and their respective access URIs as value.
         */
        $app->get('/api/sensors/firmware', function() use ($app, $em, $services, $config, $messages) {
            $controller = new Sensors($em, $services, $config);
            $sensor = $controller->checkSensorCert();
            if(!V::objectType()->validate($sensor)) throw new ForbiddenException();
            echo json_encode($controller->getFirmwareURIs($sensor));
        });

        $app->post('/api/sensors', function() use ($app, $em, $services, $config, $messages) {
            $controller = new Sensors($em, $services, $config);
            $request = $app->request()->getBody();
            V::json()->check($request);
            $sensorData = json_decode($request);
            $sensor = $controller->create($sensorData);
            echo json_encode($sensor->getState());
        });

        // Used by sensors to send their status data and receive current configuration
        $app->post('/api/sensors/status', function() use ($app, $em, $services, $config, $messages) {
            $controller = new Sensors($em, $services, $config);
            $sensor = $controller->checkSensorCert();
            if(!V::objectType()->validate($sensor)) throw new ForbiddenException();
            $request = $app->request()->getBody();
            V::json()->check($request);
            $statusData = json_decode($request);
            V::objectType()
                ->attribute('sensor', V::intVal())
                ->check($statusData);
            $status = $controller->createStatus($statusData);
            $controller->reduce($statusData->sensor, 10);
            // Collect sensor configuration and send it as response
            $sensorData = $status->getSensor()->getState();
            if($status->getSensor()->getServerEndpointMode() == Sensor::SERVER_ENDPOINT_MODE_DEFAULT) {
                $sensorData['server_endpoint_host'] = $config['server']['host'];
                $sensorData['server_endpoint_port_https'] = $config['server']['portHTTPS'];
            }
            $sensor = $status->getSensor();
            // Replace the update interval with the global default if no custom value was set for the sensor
            $sensorData['update_interval'] = $sensor->getUpdateInterval() != null ?
                $sensor->getUpdateInterval() : $config['sensors']['update_interval'];
            // Replace the service network with the global default if no custom value was set for the sensor
            $sensorData['service_network'] = $sensor->getServiceNetwork() != null ?
                $sensor->getServiceNetwork() : $config['sensors']['service_network'];
            // Replace service assignments with elaborate service data
            $services = array();
            $serviceRepository = $em->getRepository('HoneySens\app\models\entities\Service');
            $revisionRepository = $em->getRepository('HoneySens\app\models\entities\ServiceRevision');
            foreach($sensorData['services'] as $serviceAssignment) {
                $service = $serviceRepository->find($serviceAssignment['service']);
                $revisions = $service->getDistinctRevisions();
                // TODO getDefaultRevision() returns a string, $servieAssignment['revision'] returns int IDs (so far unused)
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
            $platformRepository = $em->getRepository('HoneySens\app\models\entities\Platform');
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
            $qb = $controller->getEntityManager()->createQueryBuilder();
            $unhandledEventCount = $qb->select('COUNT(e.id)')
                ->from('HoneySens\app\models\entities\Event', 'e')
                ->join('e.sensor', 's')
                ->andWhere('s.id = :sensor')
                ->andWhere('e.status = :status')
                ->setParameter('sensor', $statusData->sensor)
                ->setParameter('status', 0)
                ->getQuery()->getSingleScalarResult();
            $sensorData['unhandledEvents'] = $unhandledEventCount != 0;
            // If the sensor cert fingerprint was sent and differs from the current cert, include updated cert data
            if(V::attribute('crt_fp', V::stringType())->validate($statusData) && $sensorData['crt_fp'] != $statusData->crt_fp)
                $sensorData['sensor_crt'] = $sensor->getCert()->getContent();
            // If the server cert fingerprint was sent and differs from the current (or soon-to-be) TLS cert, include updated cert data
            $srvCert = $controller->getServerCert();
            if(V::attribute('srv_crt_fp', V::stringType())->validate($statusData) && openssl_x509_fingerprint($srvCert, 'sha256') != $statusData->srv_crt_fp)
                $sensorData['server_crt'] = $srvCert;
            // If the EAPOL CA cert fingerprint was sent and differs, include updated cert
            $caCertFP = $sensor->getEAPOLCACert() == null ? null : $sensor->getEAPOLCACert()->getFingerprint();
            if(V::attribute('eapol_ca_crt_fp', V::optional(V::stringType()))->validate($statusData) && $caCertFP != $statusData->eapol_ca_crt_fp)
                $sensorData['eapol_ca_cert'] = $sensor->getEAPOLCACert() == null ? null : $sensor->getEAPOLCACert()->getContent();
            else unset($sensorData['eapol_ca_cert']);
            // If the EAPOL TLS cert fingerprint was sent and differs, include updated cert and key
            $clientCertFP = $sensor->getEAPOLClientCert() == null ? null : $sensor->getEAPOLClientCert()->getFingerprint();
            if(V::attribute('eapol_client_crt_fp', V::optional(V::stringType()))->validate($statusData) && $clientCertFP != $statusData->eapol_client_crt_fp) {
                $sensorData['eapol_client_cert'] = $sensor->getEAPOLClientCert() == null ? null : $sensor->getEAPOLClientCert()->getContent();
                $sensorData['eapol_client_key'] = $sensor->getEAPOLClientCert() == null ? null : $sensor->getEAPOLClientCert()->getKey();
            } else unset($sensorData['eapol_client_cert']);
            echo json_encode($sensorData);
        });

        $app->put('/api/sensors/:id', function($id) use ($app, $em, $services, $config, $messages) {
            $controller = new Sensors($em, $services, $config);
            $request = $app->request()->getBody();
            V::json()->check($request);
            $sensorData = json_decode($request);
            $sensor = $controller->update($id, $sensorData);
            echo json_encode($sensor->getState());
        });

        $app->delete('/api/sensors/:id', function($id) use ($app, $em, $services, $config, $messages) {
            $controller = new Sensors($em, $services, $config);
            $controller->delete($id);
            echo json_encode([]);
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
            return $qb->getQuery()->getSingleResult()->getState();
        } else {
            $sensors = array();
            foreach($qb->getQuery()->getResult() as $sensor) {
                $sensors[] = $sensor->getState();
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
     * @param $sensor
     * @return array
     */
    public function getFirmwareURIs($sensor) {
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
     * @param \stdClass $data
     * @return Sensor
     * @throws ForbiddenException
     */
    public function create($data) {
        $this->assureAllowed('create');
        // Validation
        V::objectType()
            ->attribute('name', V::alnum('_-.')->length(1, 50))
            ->attribute('location', V::stringType()->length(0, 255))
            ->attribute('division', V::intVal())
            ->attribute('eapol_mode', V::intVal()->between(0, 4))
            ->attribute('server_endpoint_mode', V::intVal()->between(0, 1))
            ->attribute('network_ip_mode', V::intVal()->between(0, 2))
            ->attribute('network_mac_mode', V::intVal()->between(0, 1))
            ->attribute('proxy_mode', V::intVal()->between(0, 1))
            ->attribute('update_interval', V::optional(V::intVal()->between(1, 60)))
            ->attribute('service_network', V::optional(V::stringType()->length(9, 18)))
            ->attribute('firmware', V::optional(V::intVal()))
            ->check($data);
        $em = $this->getEntityManager();
        $division = $em->getRepository('HoneySens\app\models\entities\Division')->find($data->division);
        V::objectType()->check($division);
        $firmware = null;
        if($data->firmware != null) {
            $firmware = $em->getRepository('HoneySens\app\models\entities\Firmware')->find($data->firmware);
            V::objectType()->check($firmware);
        }
        // Persistence
        $sensor = new Sensor();
        $sensor->setName($data->name)
            ->setLocation($data->location)
            ->setDivision($division)
            ->setEAPOLMode($data->eapol_mode)
            ->setServerEndpointMode($data->server_endpoint_mode)
            ->setNetworkIPMode($data->network_ip_mode)
            ->setNetworkMACMode($data->network_mac_mode)
            ->setProxyMode($data->proxy_mode)
            ->setUpdateInterval($data->update_interval)
            ->setServiceNetwork($data->service_network)
            ->setFirmware($firmware);
        // Validate and persist additional attributes depending on the previous ones
        if($sensor->getServerEndpointMode() == Sensor::SERVER_ENDPOINT_MODE_CUSTOM) {
            V::attribute('server_endpoint_host', V::stringType()->ip())
                ->attribute('server_endpoint_port_https', V::intVal()->between(1, 65535))
                ->check($data);
            $sensor->setServerEndpointHost($data->server_endpoint_host)
                ->setServerEndpointPortHTTPS($data->server_endpoint_port_https);
        }
        if($sensor->getNetworkIPMode() == Sensor::NETWORK_IP_MODE_STATIC) {
            V::attribute('network_ip_address', V::stringType()->ip())
                ->attribute('network_ip_netmask', V::stringType()->ip())
                ->attribute('network_ip_gateway', V::optional(V::stringType()->ip()))
                ->attribute('network_ip_dns', V::optional(V::stringType()->ip()))
                ->check($data);
            $sensor->setNetworkIPAddress($data->network_ip_address)
                ->setNetworkIPNetmask($data->network_ip_netmask)
                ->setNetworkIPGateway($data->network_ip_gateway)
                ->setNetworkIPDNS($data->network_ip_dns);
        }
        if($sensor->getNetworkMACMode() == Sensor::NETWORK_MAC_MODE_CUSTOM) {
            V::attribute('network_mac_address', V::stringType()->macAddress())
                ->check($data);
            $sensor->setNetworkMACAddress($data->network_mac_address);
        }
        if($sensor->getProxyMode() == Sensor::PROXY_MODE_ENABLED) {
            V::attribute('proxy_host', V::stringType())
                ->attribute('proxy_port', V::intVal()->between(0, 65535))
                ->attribute('proxy_user', V::optional(V::stringType()))
                ->check($data);
            $sensor->setProxyHost($data->proxy_host)
                ->setProxyPort($data->proxy_port);
            if(strlen($data->proxy_user) > 0) {
                $sensor->setProxyUser($data->proxy_user);
                // Only set a password if one was provided by the client
                if(V::attribute('proxy_password', V::stringType())->validate($data)) {
                    $sensor->setProxyPassword($data->proxy_password);
                }
            }
            else $sensor->setProxyUser(null);
        }
        if($sensor->getEAPOLMode() != Sensor::EAPOL_MODE_DISABLED) {
            V::attribute('eapol_identity', V::stringType()->length(1, 512))->check($data);
            $sensor->setEAPOLIdentity($data->eapol_identity);
            if($sensor->getEAPOLMode() == Sensor::EAPOL_MODE_MD5) {
                V::attribute('eapol_password', V::optional(V::stringType()->length(1, 512)))->check($data);
                $sensor->setEAPOLPassword($data->eapol_password);
            } else {
                // For the other modes, a CA cert can be specified
                V::attribute('eapol_ca_cert', V::optional(V::stringType()))->check($data);
                if($data->eapol_ca_cert != null) {
                    $cert = $this->verifyCertificate($data->eapol_ca_cert);
                    $caCert = new SSLCert();
                    $caCert->setContent($cert);
                    $em->persist($caCert);
                    $sensor->setEAPOLCACert($caCert);
                };
                if($sensor->getEAPOLMode() == Sensor::EAPOL_MODE_TLS) {
                    V::attribute('eapol_client_cert', V::stringType())
                        ->attribute('eapol_client_key', V::stringType())
                        ->attribute('eapol_client_key_password', V::optional(V::stringType()->length(1, 512)))
                        ->check($data);
                    $cert = $this->verifyCertificate($data->eapol_client_cert);
                    $key = $this->verifyKey($data->eapol_client_key, $data->eapol_client_key_password == null ? '' : $data->eapol_client_key_password);
                    $clientCert = new SSLCert();
                    $clientCert->setContent($cert)->setKey($key);
                    $em->persist($clientCert);
                    $sensor->setEAPOLClientCert($clientCert);
                    $sensor->setEAPOLClientCertPassphrase($data->eapol_client_key_password);
                } else {
                    // PEAP or TTLS
                    V::attribute('eapol_password', V::optional(V::stringType()->length(1, 512)))
                        ->attribute('eapol_anon_identity', V::optional(V::stringType()->length(1, 512)))
                        ->check($data);
                    $sensor->setEAPOLPassword($data->eapol_password);
                    $sensor->setEAPOLAnonymousIdentity($data->eapol_anon_identity);
                }
            }
        }
        $em->persist($sensor);
        // Flush early, because we need the sensor ID for the cert common name
        $em->flush();
        $this->regenerateCert($sensor);
        // TODO Config archive status is not necessary anymore
        $sensor->setConfigArchiveStatus(Sensor::CONFIG_ARCHIVE_STATUS_SCHEDULED);
        $em->flush();
        return $sensor;
    }

    /**
     * Registers new status data from a sensor.
     * The given data object should have the following attributes:
     * - status: The actual status data as JSON object, encoded in base64
     * - sensor: Sensor id
     * - signature: Base64 encoded signature of the 'status' value
     *
     * The status data JSON object has to consist of the following attributes:
     * - timestamp: UNIX timestamp of the current sensor time
     * - status: Flat that indicates the current sensor status (0 to 4)
     * - ip: IP address of the sensor's primary network interface
     * - free_mem: Free RAM on the sensor
     * - sw_version: Current sensor firmware revision
     *
     * TODO The following objects are only optional to preserve API compatibility with older sensors
     * The status data JSON object also MAY contain the following attributes:
     * - service_status: associative JSON array {service_name: service_status, ...}
     *
     * @param \stdClass $data
     * @return SensorStatus
     * @throws BadRequestException
     */
    public function createStatus($data) {
        // Validation
        V::objectType()
            ->attribute('status', V::stringType())
            ->attribute('sensor', V::intVal())
            ->attribute('signature', V::stringType())
            ->check($data);
        $statusDataDecoded = base64_decode($data->status);
        V::json()->check($statusDataDecoded);
        $statusData = json_decode($statusDataDecoded);
        V::objectType()
            ->attribute('timestamp', V::intVal())
            ->attribute('status', V::intVal()->between(0, 4))
            ->attribute('ip', V::stringType())
            ->attribute('free_mem', V::intVal())
            ->attribute('disk_usage', V::intVal())
            ->attribute('disk_total', V::intVal())
            ->attribute('sw_version', V::stringType())
            ->attribute('service_status', V::objectType()->each(V::intVal()->between(0,2), V::stringType()), false)
            ->check($statusData);
        $em = $this->getEntityManager();
        $sensor = $em->getRepository('HoneySens\app\models\entities\Sensor')->find($data->sensor);
        V::objectType()->check($sensor);
        // Check timestamp validity: only accept timestamps that aren't older than two minutes
        $now = new \DateTime();
        if(($sensor->getLastStatus() != null && $statusData->timestamp < $sensor->getLastStatus()->getTimestamp()->format('U'))
            || $statusData->timestamp < ($now->format('U') - 120)) {
            // TODO Invalid timestamp return value
            throw new BadRequestException();
        }
        // Check sensor cert validity
        $cert = $sensor->getCert();
        $x509 = new X509();
        $x509->loadCA(file_get_contents(APPLICATION_PATH . '/../data/CA/ca.crt'));
        $x509->loadX509($cert->getContent());
        if(!$x509->validateSignature()) {
            // TODO Invalid sensor cert return value
            throw new BadRequestException();
        }
        // Check signature validity
        if(!openssl_verify(base64_decode($data->status), base64_decode($data->signature), $cert->getContent())) {
            // TODO Invalid signature return value
            throw new BadRequestException();
        }
        // Persistence
        $status = new SensorStatus();
        $timestamp = new \DateTime('@' . $statusData->timestamp);
        $timestamp->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $status->setSensor($sensor)
            ->setTimestamp($timestamp)
            ->setStatus($statusData->status)
            ->setIP($statusData->ip)
            ->setFreeMem($statusData->free_mem)
            ->setDiskUsage($statusData->disk_usage)
            ->setDiskTotal($statusData->disk_total)
            ->setSWVersion($statusData->sw_version);
        if(property_exists($statusData, 'service_status')) $status->setServiceStatus($statusData->service_status);
        $em->persist($status);
        $em->flush();
        return $status;
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
     * @param \stdClass $data
     * @return Sensor
     * @throws ForbiddenException
     * @throws BadRequestException
     */
    public function update($id, $data) {
        $this->assureAllowed('update');
        // Validation
        V::intVal()->check($id);
        V::objectType()
            ->attribute('name', V::alnum('_-.')->length(1, 50))
            ->attribute('location', V::stringType()->length(0, 255))
            ->attribute('division', V::intVal())
            ->attribute('eapol_mode', V::intVal()->between(0, 4))
            ->attribute('server_endpoint_mode', V::intVal()->between(0, 1))
            ->attribute('network_ip_mode', V::intVal()->between(0, 2))
            ->attribute('network_mac_mode', V::intVal()->between(0, 1))
            ->attribute('proxy_mode', V::intVal()->between(0, 1))
            ->attribute('update_interval', V::optional(V::intVal()->between(1, 60)))
            ->attribute('service_network', V::optional(V::regex('/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\/(?:30|2[0-9]|1[0-9]|[1-9]?)$/')))
            ->attribute('firmware', V::optional(V::intVal()))
            ->attribute('services', V::arrayVal()->each(V::objectType()
                ->attribute('service', V::intVal())
                ->attribute('revision')
            ))->check($data);
        $em = $this->getEntityManager();
        $sensor = $em->getRepository('HoneySens\app\models\entities\Sensor')->find($id);
        V::objectType()->check($sensor);
        // Persistence
        $sensor->setName($data->name);
        $sensor->setLocation($data->location);
        // TODO Move this sensor's events to the new Division, too
        $division = $em->getRepository('HoneySens\app\models\entities\Division')->find($data->division);
        V::objectType()->check($division);
        $sensor->setDivision($division);
        $sensor->setServerEndpointMode($data->server_endpoint_mode);
        if($sensor->getServerEndpointMode() == Sensor::SERVER_ENDPOINT_MODE_CUSTOM) {
            V::attribute('server_endpoint_host', V::stringType()->ip())
                ->attribute('server_endpoint_port_https', V::intVal()->between(1, 65535))
                ->check($data);
            $sensor->setServerEndpointHost($data->server_endpoint_host)
                ->setServerEndpointPortHTTPS($data->server_endpoint_port_https);
        } else {
            $sensor->setServerEndpointHost(null)
                ->setServerEndpointPortHTTPS(null);
        }
        $sensor->setNetworkIPMode($data->network_ip_mode);
        if($sensor->getNetworkIPMode() == Sensor::NETWORK_IP_MODE_STATIC) {
            V::attribute('network_ip_address', V::stringType()->ip())
                ->attribute('network_ip_netmask', V::stringType()->ip())
                ->attribute('network_ip_gateway', V::optional(V::stringType()->ip()))
                ->attribute('network_ip_dns', V::optional(V::stringType()->ip()))
                ->check($data);
            $sensor->setNetworkIPAddress($data->network_ip_address)
                ->setNetworkIPNetmask($data->network_ip_netmask)
                ->setNetworkIPGateway($data->network_ip_gateway)
                ->setNetworkIPDNS($data->network_ip_dns);
        } else {
            $sensor->setNetworkIPAddress(null)
                ->setNetworkIPNetmask(null)
                ->setNetworkIPGateway(null)
                ->setNetworkIPDNS(null);
        }
        $sensor->setNetworkMACMode($data->network_mac_mode);
        if($sensor->getNetworkMACMode() == Sensor::NETWORK_MAC_MODE_CUSTOM) {
            V::attribute('network_mac_address', V::stringType()->macAddress())
                ->check($data);
            $sensor->setNetworkMACAddress($data->network_mac_address);
        } else {
            $sensor->setNetworkMACAddress(null);
        }
        $sensor->setProxyMode($data->proxy_mode);
        if($sensor->getProxyMode() == Sensor::PROXY_MODE_ENABLED) {
            V::attribute('proxy_host', V::stringType())
                ->attribute('proxy_port', V::intVal()->between(0, 65535))
                ->attribute('proxy_user', V::optional(V::stringType()))
                ->check($data);
            $sensor->setProxyHost($data->proxy_host)
                ->setProxyPort($data->proxy_port);
            if(strlen($data->proxy_user) > 0) {
                $sensor->setProxyUser($data->proxy_user);
                // Only change the password if one was explicitly submitted
                if(V::attribute('proxy_password', V::stringType())->validate($data)) {
                    $sensor->setProxyPassword($data->proxy_password);
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
        $sensor->setEAPOLMode($data->eapol_mode);
        if($sensor->getEAPOLMode() != Sensor::EAPOL_MODE_DISABLED) {
            V::attribute('eapol_identity', V::stringType()->length(1, 512))->check($data);
            $sensor->setEAPOLIdentity($data->eapol_identity);
            if($sensor->getEAPOLMode() == Sensor::EAPOL_MODE_MD5) {
                V::attribute('eapol_password', V::optional(V::stringType()->length(1, 512)), false)->check($data);
                // Only update the password if it was specified, otherwise keep the existing one
                if(V::attribute('eapol_password')->validate($data)) $sensor->setEAPOLPassword($data->eapol_password);
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
                V::attribute('eapol_ca_cert', V::optional(V::stringType()), false)->check($data);
                // If a CA cert wasn't specified, just keep the existing one and do nothing
                if(V::attribute('eapol_ca_cert')->validate($data)) {
                    if($data->eapol_ca_cert == null) {
                        // Remove CA cert (if there was one set previously)
                        if($sensor->getEAPOLCACert() != null) {
                            $em->remove($sensor->getEAPOLCACert());
                            $sensor->setEAPOLCACert(null);
                        }
                    } else {
                        // Attribute was specified with a value: overwrite existing CA cert or create new one
                        $cert = $this->verifyCertificate($data->eapol_ca_cert);
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
                    V::attribute('eapol_client_cert', V::stringType(), false)
                        ->attribute('eapol_client_key', V::stringType(), false)
                        ->attribute('eapol_client_key_password', V::optional(V::stringType()->length(1, 512)), false)
                        ->check($data);
                    if(V::attribute('eapol_client_key_password')->validate($data)) $sensor->setEAPOLClientCertPassphrase($data->eapol_client_key_password);
                    if(V::attribute('eapol_client_cert')->validate($data) && V::attribute('eapol_client_key')->validate($data)) {
                        // Attribute was specified with a value: overwrite existing client cert or create new one
                        $cert = $this->verifyCertificate($data->eapol_client_cert);
                        $key = $this->verifyKey($data->eapol_client_key, $sensor->getEAPOLClientCertPassphrase() == null ? '' : $sensor->getEAPOLClientCertPassphrase());
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
                    V::attribute('eapol_password', V::optional(V::stringType()->length(1, 512)), false)
                        ->attribute('eapol_anon_identity', V::optional(V::stringType()->length(1, 512)))
                        ->check($data);
                    // Keep the existing password if none was given
                    if(V::attribute('eapol_password')->validate($data)) $sensor->setEAPOLPassword($data->eapol_password);
                    $sensor->setEAPOLAnonymousIdentity($data->eapol_anon_identity);
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
        if($data->firmware != null) {
            $firmware = $em->getRepository('HoneySens\app\models\entities\Firmware')->find($data->firmware);
            V::objectType()->check($firmware);
        }
        $sensor->setFirmware($firmware);
        $sensor->setUpdateInterval($data->update_interval);
        $sensor->setServiceNetwork($data->service_network);
        // Service handling, merge with existing data
        $serviceRepository = $em->getRepository('HoneySens\app\models\entities\Service');
        $revisionRepository = $em->getRepository('HoneySens\app\models\entities\ServiceRevision');
        // Clone the collection into an array so that newly added models won't interfere with the removal process
        $assignments = $sensor->getServices()->toArray();
        // Add/Update of service assignments
        $handledAssignments = array();
        foreach($data->services as $serviceAssignment) {
            $assigned = false;
            // Validate availability of the assignment
            $service = $serviceRepository->find($serviceAssignment->service);
            V::objectType()->check($service);
            $revision = $serviceAssignment->revision == null ? null : $revisionRepository->find($serviceAssignment->revision);
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
        return $sensor;
    }

    public function delete($id) {
        $this->assureAllowed('delete');
        // Validation
        V::intVal()->check($id);
        $em = $this->getEntityManager();
        $sensor = $em->getRepository('HoneySens\app\models\entities\Sensor')->find($id);
        V::objectType()->check($sensor);
        // Remove all events that belong to this sensor
        // TODO Consider moving those into some sort of archive
        $events = $em->getRepository('HoneySens\app\models\entities\Event')->findBy(array('sensor' => $sensor));
        foreach($events as $event) $em->remove($event);
        $em->remove($sensor);
        $em->flush();
    }

    /**
     * Triggers the creation and file download of a new sensor configuration archive.
     *
     * @param int $id Sensor id of the config archive that was requested
     * @return
     * @throws ForbiddenException
     */
    public function requestConfigDownload($id) {
        // TODO Verify that requesting user is allowed to download that specific config
        $this->assureAllowed('downloadConfig');
        // Validation
        V::intVal()->check($id);
        $sensor = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\Sensor')->find($id);
        V::objectType()->check($sensor);
        // Enqueue a new task and return it, it's the client's obligation to check that task's status and download the result
        $taskParams = $sensor->getState();
        $taskParams['cert'] = $sensor->getCert()->getContent();
        $taskParams['key'] = $sensor->getCert()->getKey();
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
        return $task->getState();
    }

    /**
     * Removes the oldest status entries of a particular sensor
     *
     * @param int $sensor_id The id of the sensor to clean up for
     * @param int $keep The number of entries to keep
     */
    public function reduce($sensor_id, $keep) {
        // Validation
        V::intVal()->check($sensor_id);
        V::intVal()->check($keep);
        // Persistence
        $em = $this->getEntityManager();
        $statusSorted = array();
        $sensor = $em->getRepository('HoneySens\app\models\entities\Sensor')->find($sensor_id);
        V::objectType()->check($sensor);
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
     * For the given sensor, regenerates a new private key (if non exists yet) and issues a new signed certificate.
     *
     * @param Sensor $sensor The sensor to regenerate a certificate for
     * @param string $caCertPath Path to the CA certificate that is used to sign the certificates
     */
    public function regenerateCert($sensor, $caCertPath = APPLICATION_PATH . '/../data/CA/ca.crt') {
        // Validation
        V::objectType()->check($sensor);
        $em = $this->getEntityManager();
        // Generate new cert data
        $config = array('config' => APPLICATION_PATH . '/../data/CA/openssl_ca.cnf');
        $cacert = 'file://' . $caCertPath;
        $cakey = array('file://' . APPLICATION_PATH . '/../data/CA/ca.key', 'asdf');
        $dn = array('commonName' => $sensor->getHostname());
        // Use a private key that probably already exists
        if($sensor->getCert() != null) $privkey = $sensor->getCert()->getKey();
        else $privkey = openssl_pkey_new($config);
        $csr = openssl_csr_new($dn, $privkey, $config);
        $usercert = openssl_csr_sign($csr, $cacert, $cakey, 365, $config);
        openssl_x509_export($usercert, $certout);
        openssl_pkey_export($privkey, $pkeyout);
        $cert = new SSLCert();
        $cert->setContent($certout);
        $cert->setKey($pkeyout);
        $oldCert = $sensor->getCert();
        $sensor->setCert($cert);
        // Remove an existing cert, in case there is one
        if($oldCert != null) $em->remove($oldCert);
        $em->persist($cert);
        $em->flush();
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
}
