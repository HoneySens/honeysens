<?php
namespace HoneySens\app\services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use HoneySens\app\adapters\TaskAdapter;
use HoneySens\app\models\constants\EventStatus;
use HoneySens\app\models\constants\LogResource;
use HoneySens\app\models\constants\SensorEAPOLMode;
use HoneySens\app\models\constants\SensorNetworkIPMode;
use HoneySens\app\models\constants\SensorNetworkMACMode;
use HoneySens\app\models\constants\SensorProxyMode;
use HoneySens\app\models\constants\SensorServerEndpointMode;
use HoneySens\app\models\constants\TaskType;
use HoneySens\app\models\constants\UserRole;
use HoneySens\app\models\entities\Division;
use HoneySens\app\models\entities\Sensor;
use HoneySens\app\models\entities\SensorStatus;
use HoneySens\app\models\entities\ServiceAssignment;
use HoneySens\app\models\entities\SSLCert;
use HoneySens\app\models\entities\Task;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\models\exceptions\SystemException;
use HoneySens\app\services\dto\SensorParams;
use NoiseLabs\ToolKit\ConfigParser\ConfigParser;

class SensorsService extends Service {

    private ConfigParser $config;
    private EventsService $eventsService;
    private LogService $logger;
    private TaskAdapter $taskAdapter;

    public function __construct(ConfigParser $config, EntityManager $em, EventsService $eventsService, LogService $logger, TaskAdapter $taskAdapter) {
        parent::__construct($em);
        $this->config = $config;
        $this->eventsService = $eventsService;
        $this->logger = $logger;
        $this->taskAdapter = $taskAdapter;
    }

    /**
     * Fetches sensors from the DB.
     *
     * @param User $user User for which to retrieve associated entities; admins receive all entities
     * @param int|null $id ID of a specific sensor to fetch
     * @throws NotFoundException
     */
    public function get(User $user, ?int $id = null): array {
        $qb = $this->em->createQueryBuilder();
        $qb->select('s')->from('HoneySens\app\models\entities\Sensor', 's');
        if($user->role !== UserRole::ADMIN) {
            $qb->join('s.division', 'd')
                ->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $user->getId());
        }
        try {
            if ($id !== null) {
                $qb->andWhere('s.id = :id')
                    ->setParameter('id', $id);
                return $this->getSensorState($qb->getQuery()->getSingleResult());
            } else {
                $sensors = array();
                foreach ($qb->getQuery()->getResult() as $sensor) {
                    $sensors[] = $this->getSensorState($sensor);
                }
                return $sensors;
            }
        } catch (NonUniqueResultException|NoResultException) {
            throw new NotFoundException();
        }
    }

    /**
     * Creates a new sensor.
     *
     * @param User $user Session user that calls this service
     * @param SensorParams $params Sensor attributes
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws SystemException
     * @todo Config archive status is deprecated, remove it
     */
    public function create(User $user, SensorParams $params): Sensor {
        try {
            $division = $this->em->getRepository('HoneySens\app\models\entities\Division')->find($params->divisionID);
            if ($division === null) throw new NotFoundException();
            if($user->role !== UserRole::ADMIN)
                $this->assureUserAffiliation($division->getId(), $user->getId());
            $sensor = new Sensor();
            $this->em->persist($sensor);
            $this->updateSensorFromParams($sensor, $params, $division);
            $sensor->setConfigArchiveStatus(Sensor::CONFIG_ARCHIVE_STATUS_SCHEDULED);
            $this->em->flush();
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        $this->logger->log(sprintf('Sensor %s (ID %d) created', $sensor->getName(), $sensor->getId()), LogResource::SENSORS, $sensor->getId());
        return $sensor;
    }

    /**
     * Updates both the attributes and associated services of an existing sensor.
     *
     * @todo Move this sensor's events to the new Division
     * @param int $id Sensor ID to update
     * @param User $user Session user that calls this service
     * @param SensorParams $params Updated sensor parameters
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws SystemException
     */
    public function update(int $id, User $user, SensorParams $params): Sensor {
        try {
            $sensor = $this->em->getRepository('HoneySens\app\models\entities\Sensor')->find($id);
            if ($sensor === null) throw new NotFoundException();
            $division = $this->em->getRepository('HoneySens\app\models\entities\Division')->find($params->divisionID);
            if ($division === null) throw new NotFoundException();
            if($user->role !== UserRole::ADMIN)
                $this->assureUserAffiliation($division->getId(), $user->getId());
            $this->updateSensorFromParams($sensor, $params, $division);
            // Update sensor services
            $serviceRepository = $this->em->getRepository('HoneySens\app\models\entities\Service');
            $revisionRepository = $this->em->getRepository('HoneySens\app\models\entities\ServiceRevision');
            // Clone the collection into an array so that newly added models won't interfere with the removal process
            $assignments = $sensor->getServices()->toArray();
            // Add/Update of service assignments
            $handledAssignments = array();
            foreach($params->services as $serviceAssignment) {
                $assigned = false;
                // Validate availability of the assignment
                $service = $serviceRepository->find($serviceAssignment['service']);
                // Ignore invalid service assignments to not end up in an inconsistent sensor state
                if($service === null) continue;
                $revision = $serviceAssignment['revision'] === null ? null : $revisionRepository->find($serviceAssignment['revision']);
                // TODO Check if revision belongs to service
                // Update existing assignment
                foreach($assignments as $assignment) {
                    if($assignment->getService()->getId() === $service->getId()) {
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
                    $this->em->persist($newAssignment);
                }
            }
            // Deletion of remaining service assignments
            foreach(array_udiff($assignments, $handledAssignments, function($a, $b) {return strcmp(spl_object_hash($a), spl_object_hash($b));}) as $deletionCandidate) {
                $deletionCandidate->getSensor()->removeService($deletionCandidate);
                $deletionCandidate->getService()->removeAssignment($deletionCandidate);
                $deletionCandidate->setRevision(null);
                $this->em->remove($deletionCandidate);
            }
            $this->em->flush();
            $this->logger->log(sprintf('Sensor %s (ID %d) updated', $sensor->getName(), $sensor->getId()), LogResource::SENSORS, $sensor->getId());
            return $sensor;
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
    }

    /**
     * Removes a sensor.
     *
     * @param int $id Sensor ID to delete
     * @param bool $archive If set, all events of this sensor are sent to the archive
     * @param User $user Session user that calls this service
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws SystemException
     */
    public function delete(int $id, bool $archive, User $user) {
        try {
            $sensor = $this->em->getRepository('HoneySens\app\models\entities\Sensor')->find($id);
            if ($sensor === null) throw new BadRequestException();
            if($user->role !== UserRole::ADMIN)
                $this->assureUserAffiliation($sensor->getDivision()->getId(), $user->getId());
            // (Archive and) Remove all events that belong to this sensor
            $events = $this->em->getRepository('HoneySens\app\models\entities\Event')->findBy(array('sensor' => $sensor));
            if ($archive) {
                $eventIDs = array_map(function ($e) {
                    return $e->getId();
                }, $events);
                $this->eventsService->archiveEvents($eventIDs);
            }
            foreach ($events as $event) $this->em->remove($event);
            $sid = $sensor->getId();
            $this->em->remove($sensor);
            $this->em->flush();
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        $this->logger->log(sprintf('Sensor %s (ID %d) deleted', $sensor->getName(), $sid), LogResource::SENSORS, $sid);
    }

    /**
     * Fetches all status data entries for a specific sensor.
     *
     * @param int $sensorId Sensor ID to fetch status data for
     * @param User $user Session user that calls this service
     * @throws SystemException
     */
    public function getStatus(int $sensorId, User $user): array {
        try {
            $qb = $this->em->createQueryBuilder();
            $qb->select('ss')->from('HoneySens\app\models\entities\SensorStatus', 'ss')
                ->join('ss.sensor', 's');
            if ($user->role !== UserRole::ADMIN) {
                $qb->join('s.division', 'd')
                    ->andWhere(':userid MEMBER OF d.users')
                    ->setParameter('userid', $user->getId());
            }
            $qb->andWhere('s.id = :id')->setParameter('id', $sensorId);
            $stati = array();
            foreach ($qb->getQuery()->getResult() as $status) {
                $stati[] = $status->getState();
            }
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        return $stati;
    }

    /**
     * Triggers the creation and file download of a sensor configuration archive.
     *
     * @param int $id Sensor id of the config archive that was requested
     * @throws ForbiddenException
     * @throws NotFoundException
     * @throws SystemException
     */
    public function requestConfigDownload(int $id, User $user): Task {
        try {
            $sensor = $this->em->getRepository('HoneySens\app\models\entities\Sensor')->find($id);
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        if($sensor === null) throw new NotFoundException();
        if($user->role !== UserRole::ADMIN)
            $this->assureUserAffiliation($sensor->getDivision()->getId(), $user->getId());
        // Enqueue a new task and return it, it's the client's obligation to check that task's status and download the result
        $taskParams = $this->getSensorState($sensor);
        $taskParams['secret'] = $sensor->getSecret();
        // If this sensor doesn't have a custom service network defined, we rely on the system-wide configuration
        $taskParams['service_network'] = $sensor->getServiceNetwork() != null ? $sensor->getServiceNetwork() : $this->config['sensors']['service_network'];
        if($sensor->getServerEndpointMode() === SensorServerEndpointMode::DEFAULT) {
            $taskParams['server_endpoint_host'] = $this->config['server']['host'];
            $taskParams['server_endpoint_port_https'] = $this->config['server']['portHTTPS'];
        }
        $taskParams['server_endpoint_name'] = $this->config['server']['host'];
        $taskParams['proxy_password'] = $sensor->getProxyPassword();
        $taskParams['eapol_password'] = $sensor->getEAPOLPassword();
        $taskParams['eapol_client_key_password'] = $sensor->getEAPOLClientCertPassphrase();
        if($sensor->getEAPOLCACert() !== null) $taskParams['eapol_ca_cert'] = $sensor->getEAPOLCACert()->content;
        if($sensor->getEAPOLClientCert() !== null) {
            $taskParams['eapol_client_cert'] = $sensor->getEAPOLClientCert()->content;
            $taskParams['eapol_client_key'] = $sensor->getEAPOLClientCert()->key;
        } else $taskParams['eapol_client_key'] = null;
        $task = $this->taskAdapter->enqueue($user, TaskType::SENSORCFG_CREATOR, $taskParams);
        return $task;
    }

    /**
     * Returns an associative array with firmware download URIs for all platforms.
     * If the given sensor overrides one of those with a specific revision, that one is returned here.
     *
     * @param Sensor $sensor Sensor to fetch firmware URIs for.
     */
    public function getFirmwareURIs(Sensor $sensor): array {
        try {
            $platforms = $this->em->getRepository('HoneySens\app\models\entities\Platform')->findAll();
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
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
     * Processes a sensor polling event on the server by storing transmitted sensor status data
     * and returning the current sensor configuration. In case the sensor transmits certificate
     * fingerprints which don't match the current certificates for that sensor, updated certificates
     * are also added to the response.
     *
     * @param Sensor $sensor The sensor for which to register the polling event
     * @param dto\SensorStatus $statusData Status data transmitted by the sensor
     * @param string $srvCert The server's TLS certificate (for fingerprint comparison)
     * @param string|null $srvCrtFp Sensor's current server TLS certificate fingerprint
     * @param string|null $eapolCaCrtFp Sensor's current EAPOL CA certificate fingerprint
     * @param string|null $eapolClientCrtFp Sensor's current EAPOL client certificate fingerprint
     * @throws BadRequestException
     * @throws SystemException
     */
    public function poll(Sensor $sensor, \HoneySens\app\services\dto\SensorStatus $statusData, string $srvCert, ?string $srvCrtFp = null, ?string $eapolCaCrtFp = null, ?string $eapolClientCrtFp = null): array {
        $this->addSensorStatus($sensor, $statusData);
        $this->reduceStatus($sensor, 10);
        // Collect sensor configuration and send it as response
        try {
            $serviceRepository = $this->em->getRepository('HoneySens\app\models\entities\Service');
            $platformRepository = $this->em->getRepository('HoneySens\app\models\entities\Platform');
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        $sensorState = $this->getSensorState($sensor);
        if($sensor->getServerEndpointMode() === SensorServerEndpointMode::DEFAULT) {
            $sensorState['server_endpoint_host'] = $this->config['server']['host'];
            $sensorState['server_endpoint_port_https'] = $this->config['server']['portHTTPS'];
        }
        // Replace the update interval with the global default if no custom value was set for the sensor
        $sensorState['update_interval'] = $sensor->getUpdateInterval() ?? $this->config['sensors']['update_interval'];
        // Replace the service network with the global default if no custom value was set for the sensor
        $sensorState['service_network'] = $sensor->getServiceNetwork() ?? $this->config['sensors']['service_network'];
        // Replace service assignments with elaborate service data for each architecture
        $services = array();
        foreach($sensorState['services'] as $serviceAssignment) {
            $service = $serviceRepository->find($serviceAssignment['service']);
            $revisions = $service->getDistinctRevisions();
            // TODO getDefaultRevision() returns a string, $serviceAssignment['revision'] returns int IDs (so far unused)
            $targetRevision = $serviceAssignment['revision'] ?? $service->getDefaultRevision();
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
        $sensorState['services'] = count($services) > 0 ? $services : new \StdClass;
        // Send credentials exclusively to the sensors (they aren't shown inside of the web interface)
        $sensorState['proxy_password'] = $sensor->getProxyPassword();
        $sensorState['eapol_password'] = $sensor->getEAPOLPassword();
        $sensorState['eapol_client_key_password'] = $sensor->getEAPOLClientCertPassphrase();
        // Attach firmware versioning information for all platforms
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
        $sensorState['firmware'] = $firmware;
        // Unhandled event status data for physical LED indication
        $sensorState['unhandledEvents'] = $sensorState['new_events'] !== 0;
        // If the server cert fingerprint was sent and differs from the current (or soon-to-be) TLS cert, include updated cert data
        if($srvCrtFp !== null && openssl_x509_fingerprint($srvCert, 'sha256') !== $srvCrtFp)
            $sensorState['server_crt'] = $srvCert;
        // If the EAPOL CA cert fingerprint was sent and differs, include updated cert
        $caCertFP = $sensor->getEAPOLCACert()?->getFingerprint();
        if($eapolCaCrtFp && $caCertFP !== $eapolCaCrtFp)
            $sensorState['eapol_ca_cert'] = $sensor->getEAPOLCACert()?->content;
        else unset($sensorState['eapol_ca_cert']);
        // If the EAPOL TLS cert fingerprint was sent and differs, include updated cert and key
        $clientCertFP = $sensor->getEAPOLClientCert()?->getFingerprint();
        if($eapolClientCrtFp && $clientCertFP !== $eapolClientCrtFp) {
            $sensorState['eapol_client_cert'] = $sensor->getEAPOLClientCert()?->content;
            $sensorState['eapol_client_key'] = $sensor->getEAPOLClientCert()?->key;
        } else unset($sensorState['eapol_client_cert']);
        return $sensorState;
    }

    /**
     * Enriches sensor state with data acquired from external sources (such as new event count) and returns it.
     *
     * @param Sensor $sensor
     * @return array
     * @throws SystemException
     */
    public function getSensorState(Sensor $sensor): array {
        $state = $sensor->getState();
        try {
            $qb = $this->em->createQueryBuilder();
            $qb->select('count(e.id)')
                ->from('HoneySens\app\models\entities\Event', 'e')
                ->where('e.sensor = :sensor AND e.status = :status')
                ->setParameters(array('sensor' => $sensor, 'status' => EventStatus::UNEDITED));
            $state['new_events'] = intval($qb->getQuery()->getSingleScalarResult());
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        return $state;
    }

    /**
     * Attempts to parse data as an base64-encoded PEM certificate.
     * Returns the PEM-encoded X.509 certificate on success.
     *
     * @param string $data A base64-encoded PEM certificate
     * @return string
     * @throws BadRequestException
     */
    private function verifyCertificate(string $data): string {
        try {
            $decoded = base64_decode($data);
            if ($decoded) {
                $cert = openssl_x509_read($decoded);
                if ($cert) return $decoded;
            }
        } catch(\Exception) {
            throw new BadRequestException();
        }
        throw new BadRequestException();
    }

    /**
     * Attempts to parse data as an base64-encoded PEM private key.
     * Returns the PEM-encoded private key on success.
     *
     * @param string $data A base64-encoded PEM private key
     * @param string|null $passphrase Optional passphrase to decrypt the key
     * @throws BadRequestException
     */
    private function verifyKey(string $data, ?string $passphrase): string {
        try {
            $decoded = base64_decode($data);
            if ($decoded) {
                $key = openssl_pkey_get_private($decoded, $passphrase);
                if ($key) return $decoded;
            }
        } catch(\Exception) {
            throw new BadRequestException();
        }
        throw new BadRequestException();
    }

    /**
     * Registers new status data with a sensor.
     *
     * @param Sensor $sensor The sensor for which to register new status data
     * @param dto\SensorStatus $statusData Status data transmitted by that sensor
     * @throws BadRequestException
     * @throws SystemException
     */
    private function addSensorStatus(Sensor $sensor, \HoneySens\app\services\dto\SensorStatus $statusData): void {
        // Check timestamp validity: only accept timestamps that aren't older than two minutes
        $now = new \DateTime();
        if(($sensor->getLastStatus() !== null && $statusData->timestamp < $sensor->getLastStatus()->getTimestamp()->format('U'))
            || $statusData->timestamp < ($now->format('U') - 120)) {
            // TODO Consider a separate invalid timestamp return value
            throw new BadRequestException();
        }
        $status = new SensorStatus();
        $timestamp = new \DateTime('@' . $statusData->timestamp);
        $timestamp->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        // Set runningSince timestamp depending on previous sensor status
        $lastStatus = $sensor->getLastStatus();
        if($lastStatus !== null && $lastStatus->getRunningSince() !== null) {
            // Last status exists and wasn't a timeout: inherit its value
            $status->setRunningSince($lastStatus->getRunningSince());
        } else $status->setRunningSince($timestamp);
        $status->setTimestamp($timestamp)
            ->setStatus($statusData->status)
            ->setIP($statusData->ip)
            ->setFreeMem($statusData->freeMem)
            ->setDiskUsage($statusData->diskUsage)
            ->setDiskTotal($statusData->diskSize)
            ->setSWVersion($statusData->swVersion)
            ->setServiceStatus($statusData->serviceStatus);
        $sensor->addStatus($status);
        try {
            $this->em->persist($status);
            $this->em->flush();
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
    }

    /**
     * Removes the oldest status entries of a particular sensor
     *
     * @param Sensor $sensor The sensor to clean up for
     * @param int $keep The number of entries to keep
     * @throws SystemException
     */
    public function reduceStatus(Sensor $sensor, int $keep): void {
        $statusSorted = array();
        $allStatus = $sensor->getStatus();
        foreach($allStatus as $key => $status) {
            $statusSorted[$key] = $status;
            $timestamps[$key] = $status->getTimestamp();
        }
        if(count($statusSorted) > $keep) {
            try {
                array_multisort($timestamps, SORT_DESC, $statusSorted);
                $toRemove = array_slice($statusSorted, $keep);
                foreach ($toRemove as $status) {
                    $sensor->removeStatus($status);
                    $this->em->remove($status);
                }
                $this->em->flush();
            } catch(ORMException $e) {
                throw new SystemException($e);
            }
        }
    }

    /**
     * Updates a sensor's attributes from the given params, except:
     * - $params->divisionID is ignored in favor of $division
     * - $params->services is ignored (use updateSensorServices())
     * This method persists all changes made to sensor in the DB.
     *
     * @param Sensor $sensor The sensor object to update
     * @param SensorParams $params Sensor parameters to parse for attribute updates
     * @param Division $division The intended division for this sensor
     * @throws BadRequestException
     * @throws NotFoundException
     * @throws SystemException
     */
    private function updateSensorFromParams(Sensor $sensor, SensorParams $params, Division $division): void {
        $sensor->setName($params->name)
            ->setLocation($params->location)
            ->setDivision($division)
            ->setEAPOLMode($params->eapolMode)
            ->setServerEndpointMode($params->serverEndpointMode)
            ->setNetworkIPMode($params->ipMode)
            ->setNetworkMACMode($params->macMode)
            ->setProxyMode($params->proxyMode)
            ->setUpdateInterval($params->updateInterval)
            ->setServiceNetwork($params->serviceNetwork);
        try {
            $firmware = null;
            if ($params->firmwareID !== null) {
                $firmware = $this->em->getRepository('HoneySens\app\models\entities\Firmware')->find($params->firmwareID);
                if ($firmware === null) throw new NotFoundException();
            }
            $sensor->setFirmware($firmware);
            if ($sensor->getServerEndpointMode() === SensorServerEndpointMode::CUSTOM) {
                $sensor->setServerEndpointHost($params->serverEndpointHost)
                    ->setServerEndpointPortHTTPS($params->serverEndpointPort);
            } else {
                $sensor->setServerEndpointHost(null)
                    ->setServerEndpointPortHTTPS(null);
            }
            if ($sensor->getNetworkIPMode() === SensorNetworkIPMode::STATIC) {
                $sensor->setNetworkIPAddress($params->ipAddress)
                    ->setNetworkIPNetmask($params->ipNetmask)
                    ->setNetworkIPGateway($params->ipGateway)
                    ->setNetworkIPDNS($params->ipDNS);
            } else {
                $sensor->setNetworkIPAddress(null)
                    ->setNetworkIPNetmask(null)
                    ->setNetworkIPGateway(null)
                    ->setNetworkIPDNS(null);
            }
            if ($sensor->getNetworkIPMode() === SensorNetworkIPMode::DHCP) {
                $sensor->setNetworkDHCPHostname($params->dhcpHostname);
            } else {
                $sensor->setNetworkDHCPHostname(null);
            }
            if ($sensor->getNetworkMACMode() === SensorNetworkMACMode::CUSTOM) {
                $sensor->setNetworkMACAddress($params->macAddress);
            } else {
                $sensor->setNetworkMACAddress(null);
            }
            if ($sensor->getProxyMode() === SensorProxyMode::ENABLED) {
                $sensor->setProxyHost($params->proxyHost)
                    ->setProxyPort($params->proxyPort)
                    ->setProxyUser($params->proxyUser);
                if ($params->proxyUser !== null) {
                    try {
                        $sensor->setProxyPassword($params->proxyPassword);
                    } catch (\Error) {
                        // Only set a new password if one was explicitly submitted
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
            if ($sensor->getEAPOLMode() !== SensorEAPOLMode::DISABLED) {
                $sensor->setEAPOLIdentity($params->eapolIdentity);
                if ($sensor->getEAPOLMode() === SensorEAPOLMode::MD5) {
                    try {
                        $sensor->setEAPOLPassword($params->eapolPassword);
                    } catch (\Error) {
                        // Only update the password if a new one was given. Otherwise, assert we already have a password.
                        if ($sensor->getEAPOLPassword() === null) throw new BadRequestException();
                    }
                    // Reset remaining parameters
                    $sensor->setEAPOLClientCertPassphrase(null)
                        ->setEAPOLAnonymousIdentity(null);
                    if ($sensor->getEAPOLCACert() !== null) {
                        $this->em->remove($sensor->getEAPOLCACert());
                        $sensor->setEAPOLCACert(null);
                    }
                    if ($sensor->getEAPOLClientCert() !== null) {
                        $this->em->remove($sensor->getEAPOLClientCert());
                        $sensor->setEAPOLClientCert(null);
                    }
                } else {
                    // For the other modes, a CA cert is optional
                    try {
                        $eapolCACErt = $params->eapolCACert;  // Access CA cert field to trigger Error in case it's not set
                        // Remove existing CA cert
                        if ($sensor->getEAPOLCACert() !== null) {
                            $this->em->remove($sensor->getEAPOLCACert());
                            $sensor->setEAPOLCACert(null);
                        }
                        if ($eapolCACErt !== null) {
                            $certData = $this->verifyCertificate($eapolCACErt);
                            $caCert = new SSLCert();
                            $caCert->content = $certData;
                            $this->em->persist($caCert);
                            $sensor->setEAPOLCACert($caCert);
                        }
                    } catch (ORMException $e) {
                        throw new SystemException($e);
                    } catch (\Error) {
                        // If the cert attribute isn't set, keep the existing setting
                    }
                    if ($sensor->getEAPOLMode() === SensorEAPOLMode::TLS) {
                        try {
                            // Attempt to access all relevant parameters upfront. Only continue if all were supplied.
                            $sensor->setEAPOLClientCertPassphrase($params->eapolClientKeyPassword);
                            $cert = $this->verifyCertificate($params->eapolClientCert);
                            $key = $this->verifyKey($params->eapolClientKey, $sensor->getEAPOLClientCertPassphrase() === null ? '' : $sensor->getEAPOLClientCertPassphrase());
                            // Remove existing client cert
                            if ($sensor->getEAPOLClientCert() !== null) {
                                $this->em->remove($sensor->getEAPOLClientCert());
                                $sensor->setEAPOLClientCert(null);
                            }
                            // Create new client cert
                            $clientCert = new SSLCert();
                            $clientCert->content = $cert;
                            $clientCert->key = $key;
                            $this->em->persist($clientCert);
                            $sensor->setEAPOLClientCert($clientCert);
                            // Reset unused parameters
                            $sensor->setEAPOLPassword(null)
                                ->setEAPOLAnonymousIdentity(null);
                        } catch (\Error) {
                            // If new cert attributes aren't set, assert we already have a client cert
                            if ($sensor->getEAPOLClientCert() === null) throw new BadRequestException();
                        }
                    } else {
                        // PEAP or TTLS
                        try {
                            $sensor->setEAPOLPassword($params->eapolPassword);
                        } catch (\Error) {
                            // Only update the password if a new one was given. Otherwise, assert we already have a password.
                            if ($sensor->getEAPOLPassword() === null) throw new BadRequestException();
                        }
                        $sensor->setEAPOLAnonymousIdentity($params->eapolAnonIdentity);
                        // Reset unused parameters
                        if ($sensor->getEAPOLClientCert() !== null) {
                            $this->em->remove($sensor->getEAPOLClientCert());
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
                if ($sensor->getEAPOLCACert() !== null) {
                    $this->em->remove($sensor->getEAPOLCACert());
                    $sensor->setEAPOLCACert(null);
                }
                if ($sensor->getEAPOLClientCert() !== null) {
                    $this->em->remove($sensor->getEAPOLClientCert());
                    $sensor->setEAPOLClientCert(null);
                }
            }
            $this->em->flush();
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
    }
}
