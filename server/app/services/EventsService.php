<?php
namespace HoneySens\app\services;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\QueryBuilder;
use HoneySens\app\adapters\EMailAdapter;
use HoneySens\app\adapters\TaskAdapter;
use HoneySens\app\models\constants\EventClassification;
use HoneySens\app\models\constants\EventDetailType;
use HoneySens\app\models\constants\EventPacketProtocol;
use HoneySens\app\models\constants\EventService;
use HoneySens\app\models\constants\EventStatus;
use HoneySens\app\models\constants\LogResource;
use HoneySens\app\models\constants\TaskType;
use HoneySens\app\models\constants\UserRole;
use HoneySens\app\models\entities\ArchivedEvent;
use HoneySens\app\models\entities\Event;
use HoneySens\app\models\entities\EventDetail;
use HoneySens\app\models\entities\EventPacket;
use HoneySens\app\models\entities\Sensor;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\models\exceptions\SystemException;
use HoneySens\app\models\Utils;
use HoneySens\app\services\dto\EventFilterConditions;
use NoiseLabs\ToolKit\ConfigParser\ConfigParser;

class EventsService extends Service {

    private ConfigParser $config;
    private EMailAdapter $emailAdapter;
    private LogService $logger;
    private TaskAdapter $taskAdapter;

    public function __construct(ConfigParser $config, EMailAdapter $emailAdapter, EntityManager $em, LogService $logger, TaskAdapter $taskAdapter) {
        parent::__construct($em);
        $this->config = $config;
        $this->emailAdapter = $emailAdapter;
        $this->logger = $logger;
        $this->taskAdapter = $taskAdapter;
    }

    /**
     * Fetches events from the DB.
     *
     * @param User $user In case of CSV output, user to create a Task for
     * @param EventFilterConditions $conditions Filter conditions
     * @param ResponseFormat $format Essentially the ACCEPT header of the HTTP request, defines the intended output format
     * @param int $page Page number of result list (only together with $perPage)
     * @param int $perPage Number of results per page (only together with $page)
     * @throws NotFoundException
     */
    public function getEvents(User $user, EventFilterConditions $conditions, ResponseFormat $format = ResponseFormat::JSON, int $page = 0, int $perPage = 15): array {
        $qb = $this->fetchEvents($conditions);
        try {
            // Calculate the total number of results by altering the query
            $qb->select('COUNT(e.id)');
            $totalCount = $qb->getQuery()->getSingleScalarResult();
            $qb->select('e');
            // CSV output via separate task
            if ($format === ResponseFormat::CSV) {
                $qb->setFirstResult(0)
                    ->setMaxResults($totalCount);
                $taskParams = array('query' => Utils::getFullSQL($qb->getQuery()));
                $task = $this->taskAdapter->enqueue($user, TaskType::EVENT_EXTRACTOR, $taskParams);
                return $task->getState();
            }
            // JSON output
            $qb->setFirstResult($page * $perPage)
                ->setMaxResults($perPage);
            $events = array();
            foreach ($qb->getQuery()->getResult() as $event) {
                $events[] = $event->getState();
            }
            return array('items' => $events, 'total_count' => $totalCount);
        } catch(NonUniqueResultException|NoResultException|OptimisticLockException) {
            throw new NotFoundException();
        }
    }

    /**
     * Registers new events for a specific sensor.
     * Also applies matching filter rules and triggers notifications in case of critical events.
     * Classification is done while creating the event, taking into consideration the submitted data.
     *
     * The eventsData array holds data for multiple events, each being formatted as follows:
     *   [{
     *     "timestamp": <timestamp>,
     *     "service": <service>,
     *     "source": <source>,
     *     "summary": <summary>,
     *     "details": [{
     *       "timestamp": <timestamp>|null,
     *       "type": <type>,
     *       "data": <data>
     *     }, ...],
     *     "packets": [{
     *       "timestamp": <timestamp>,
     *       "protocol": <protocol>,
     *       "port": <port>,
     *       "headers": [{
     *         <field>: <value>
     *       }, ...],
     *       "payload": <payload|base64>
     *     }, ...}
     *   }, ...]
     *
     * The method returns an array of all the Event objects that were created.
     *
     * @param Sensor $sensor Sensor to register the given events with
     * @param array $eventsData List of events
     * @throws SystemException
     */
    public function createEvent(Sensor $sensor, array $eventsData): array {
        $events = array();
        foreach($eventsData as $eventData) {
            // TODO make optional fields optional (e.g. packets and details)
            $timestamp = new \DateTime('@' . $eventData['timestamp']);
            $timestamp->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            $event = new Event();
            // Save event details
            $details = array();
            foreach($eventData['details'] as $detailData) {
                if($detailData['timestamp'] === null) {
                    $detailTimestamp = null;
                } else {
                    $detailTimestamp = new \DateTime('@' . $detailData['timestamp']);
                    $detailTimestamp->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                }
                $eventDetail = new EventDetail();
                $eventDetail->timestamp = $detailTimestamp;
                $eventDetail->type = EventDetailType::from($detailData['type']);
                $eventDetail->setData($detailData['data']);
                $event->addDetails($eventDetail);
                try {
                    $this->em->persist($eventDetail);
                } catch(ORMException|OptimisticLockException $e) {
                    throw new SystemException($e);
                }
                $details[] = $eventDetail;
            }
            // Save event packets
            $packets = array();
            foreach($eventData['packets'] as $packetData) {
                $eventPacket = new EventPacket();
                $timestamp = new \DateTime('@' . $packetData['timestamp']);
                $timestamp->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                $eventPacket->timestamp = $timestamp;
                $eventPacket->protocol = EventPacketProtocol::from($packetData['protocol']);
                $eventPacket->port = $packetData['port'];
                $eventPacket->setPayload($packetData['payload']);
                foreach($packetData['headers'] as $field => $value) {
                    $eventPacket->addHeader($field, $value);
                }
                $event->addPacket($eventPacket);
                try {
                    $this->em->persist($eventPacket);
                } catch(ORMException|OptimisticLockException $e) {
                    throw new SystemException($e);
                }
                $packets[] = $eventPacket;
            }
            // Save remaining event data
            $event->timestamp = $timestamp;
            $event->sensor = $sensor;
            $event->service = EventService::from($eventData['service']);
            $event->source = $eventData['source'];
            $event->summary = $eventData['summary'];
            // Do classification
            // TODO be more sophisticated here than simply matching service and classification
            switch($event->service) {
                case EventService::RECON:
                    if($event->summary === 'Scan') $event->classification = EventClassification::PORTSCAN;
                    else $event->classification = EventClassification::CONN_ATTEMPT;
                    break;
                case EventService::DIONAEA:
                case EventService::KIPPO:
                    $event->classification = EventClassification::LOW_HP;
                    break;
                default:
                    $event->classification = EventClassification::UNKNOWN;
            }
            try {
                $this->em->persist($event);
            } catch(ORMException|OptimisticLockException $e) {
                throw new SystemException($e);
            }

            $events[] = $event;
        }
        // Apply filters
        $filters = $sensor->division->getEventFilters();
        foreach($events as $event) {
            foreach($filters as $filter) {
                if($filter->enabled && $filter->matches($event)) {
                    try {
                        $this->em->remove($event);
                    } catch(ORMException|OptimisticLockException $e) {
                        throw new SystemException($e);
                    }
                    $filter->incrementCounter();
                    break;
                }
            }
        }
        try {
            $this->em->flush();
        } catch(ORMException|OptimisticLockException $e) {
            throw new SystemException($e);
        }
        // New events should also refresh the sensors repo so that dependent UI data (e.g. new event cnt) can be updated
        if($this->em->getUnitOfWork()->size() > 0) $this->updateSensorRepo();
        try {
            // Event forwarding
            if ($this->config->getBoolean('syslog', 'enabled')) {
                foreach ($events as $event) {
                    if ($this->em->contains($event))
                        $this->taskAdapter->enqueue(null, TaskType::EVENT_FORWARDER, array('id' => $event->getId()));
                }
            }
            // Send mails for each incident
            foreach ($events as $event) {
                if ($this->em->contains($event)) $this->emailAdapter->sendIncident($this->config, $this->em, $event);
            }
        } catch(\Exception $e) {
            // If subsequent event handlers cause exceptions, fail gracefully and signal successful event creation to sensor
            error_log('Exception during post-event handling');
            error_log($e);
        }
        return $events;
    }

    /**
     * Updates a selection of events. To simplify the refresh process on the client side,
     * only status and comment fields can be updated.
     * If permissions settings require mandatory comments, each status flag update to anything other than "UNEDITED"
     * also requires a comment to be present. It's NOT possible to update archived events.
     * At least one of $newStatus or $newComment has to be setup, other this throws an Exception.
     *
     * @param EventFilterConditions $conditions Filter conditions
     * @param EventStatus|null $newStatus If given, new status flag for all selected events
     * @param string|null $newComment If given, new comment for all selected events
     * @throws BadRequestException
     * @throws SystemException
     */
    public function updateEvent(EventFilterConditions $conditions, ?EventStatus $newStatus = null, ?string $newComment = null): void {
        if($conditions->archived || ($newStatus === null && $newComment === null)) throw new BadRequestException();
        $qb = $this->fetchEvents($conditions);
        $qb->select('e.id');
        $eventIDs = $qb->getQuery()->getSingleColumnResult();
        if(sizeof($eventIDs) == 0) return;
        $qb = $this->em->createQueryBuilder()
            ->update('HoneySens\app\models\entities\Event', 'e')
            ->where('e.id IN (:ids)')->setParameter('ids', $eventIDs, ArrayParameterType::INTEGER);
        if($newStatus !== null) {
            // Enforce require_event_comment if set globally
            if($newStatus !== EventStatus::UNEDITED &&
                $this->config->getBoolean('misc', 'require_event_comment') &&
                ($newComment === null || strlen($newComment) === 0))
                    throw new BadRequestException();
            $qb->set('e.status', ':status')
                ->set('e.lastModificationTime', ':lastmod')
                ->setParameter('status', $newStatus)
                ->setParameter('lastmod', new \DateTime());
        }
        if($newComment !== null) {
            $qb->set('e.comment', ':comment')
                ->setParameter('comment', $newComment);
        }
        try {
            $qb->getQuery()->execute();
        } catch(\Exception $e) {
            throw new SystemException($e);
        }
        $this->updateSensorRepo();
        $this->logger->log('Metadata for one or multiple events updated', LogResource::EVENTS);
    }

    /**
     * Deletes and optionally archives a selection of events.
     *
     * @param EventFilterConditions $conditions Filter conditions
     * @param bool $archive Whether events should be archived before deletion
     * @throws BadRequestException
     * @throws SystemException
     */
    public function deleteEvent(EventFilterConditions $conditions, bool $archive): void {
        // We can't archive already archived events
        if($archive && $conditions->archived) throw new BadRequestException();
        $entity = $conditions->archived ? 'HoneySens\app\models\entities\ArchivedEvent' : 'HoneySens\app\models\entities\Event';
        // Batch delete events according to the remaining query parameters
        $qb = $this->fetchEvents($conditions);
        $qb->select('e.id');
        $eventIDs = $qb->getQuery()->getSingleColumnResult();
        if(sizeof($eventIDs) == 0) return;
        if($archive) $this->archiveEvents($eventIDs);
        try {
            if (!$conditions->archived) {
                // Fetch EventDetail and EventPacket IDs associated with deleted events
                $eventDetailIDs = $this->em->createQueryBuilder()
                    ->select('ed.id')->from('HoneySens\app\models\entities\EventDetail', 'ed')->join('ed.event', 'e')
                    ->where('e.id in (:ids)')->setParameter('ids', $eventIDs, ArrayParameterType::INTEGER)
                    ->getQuery()->getSingleColumnResult();
                $eventPacketIDs = $this->em->createQueryBuilder()
                    ->select('ep.id')->from('HoneySens\app\models\entities\EventPacket', 'ep')->join('ep.event', 'e')
                    ->where('e.id in (:ids)')->setParameter('ids', $eventIDs, ArrayParameterType::INTEGER)
                    ->getQuery()->getSingleColumnResult();
                // Manual delete cascade
                if (sizeof($eventDetailIDs) > 0)
                    $this->em->createQueryBuilder()
                        ->delete('HoneySens\app\models\entities\EventDetail', 'ed')
                        ->where('ed.id in (:ids)')->setParameter('ids', $eventDetailIDs, ArrayParameterType::INTEGER)
                        ->getQuery()->execute();
                if (sizeof($eventPacketIDs))
                    $this->em->createQueryBuilder()
                        ->delete('HoneySens\app\models\entities\EventPacket', 'ep')
                        ->where('ep.id in (:ids)')->setParameter('ids', $eventPacketIDs, ArrayParameterType::INTEGER)
                        ->getQuery()->execute();
            }
            $this->em->createQueryBuilder()
                ->delete($entity, 'e')
                ->where('e.id in (:ids)')->setParameter('ids', $eventIDs, ArrayParameterType::INTEGER)
                ->getQuery()
                ->execute();
        } catch(\Exception $e) {
            throw new SystemException($e);
        }
        $this->updateSensorRepo();
        $this->logger->log('One or multiple events deleted', LogResource::EVENTS);
    }

    /**
     * Sends the given events (by ID) to the archive and removes them from the primary event list.
     *
     * @param array $eventIDs List of event IDs to send to the archive
     * @throws SystemException
     */
    public function archiveEvents(array $eventIDs): void {
        if(sizeof($eventIDs) == 0) return;
        foreach($this->em->createQueryBuilder()->select('e')
                    ->from('HoneySens\app\models\entities\Event', 'e')
                    ->where('e.id IN (:ids)')
                    ->setParameter('ids', $eventIDs, ArrayParameterType::INTEGER)
                    ->getQuery()->getResult() as $event) {
            $archivedEvent = new ArchivedEvent($event);
            try {
                $this->em->persist($archivedEvent);
                $this->em->flush();
            } catch(ORMException $e) {
                throw new SystemException($e);
            }
        }
        $this->logger->log('One or multiple events archived', LogResource::EVENTS);
    }

    /**
     * Fetches details from the DB for a single event.
     *
     * @param User $user User for which to retrieve associated entities; admins receive all entities
     * @param EventDetailType $type The kind of details to fetch
     * @param int $id ID of the event to fetch
     */
    public function getEventDetails(User $user, EventDetailType $type, int $id): array {
        $qb = $this->em->createQueryBuilder();
        $entity = $type === EventDetailType::GENERIC ? 'HoneySens\app\models\entities\EventDetail' : 'HoneySens\app\models\entities\EventPacket';
        $qb->select('entity')->from($entity, 'entity')->join('entity.event', 'e');
        if($user->role !== UserRole::ADMIN) {
            $qb->join('e.sensor', 's')
                ->join('s.division', 'd')
                ->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $user->getId());
        }
        $qb->andWhere('e.id = :eventid')
            ->setParameter('eventid', $id);
        $details = array();
        foreach ($qb->getQuery()->getResult() as $detail) {
            $details[] = $detail->getState();
        }
        return $details;
    }

    /**
     * Fetches and returns event details from the event archive for a given archived event ID.
     *
     * @param User $user User for which to retrieve associated entities; admins receive all entities
     * @param int $id The archived event ID to fetch
     * @throws NotFoundException
     */
    public function getArchivedDetails(User $user, int $id): array {
        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from('HoneySens\app\models\entities\ArchivedEvent', 'e');
        if($user->role !== UserRole::ADMIN) {
            // Only join with division in case a non-admin user ID was provided so that the
            // user/event/division association can be verified. ArchivedEvents may have no longer
            // an associated division. In that case, joining with divisions eagerly would return no results.
            $qb->join('e.division', 'd')
                ->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $user->getId());
        }
        $qb->andWhere('e.id = :id')
            ->setParameter('id', $id);
        try {
            $event = $qb->getQuery()->getSingleResult();
        } catch(NonUniqueResultException|NoResultException) {
            throw new NotFoundException();
        }
        return array('details' => $event->getDetails(), 'packets' => $event->getPackets());
    }

    /**
     * Returns a QueryBuilder that selects events from the DB under the given conditions.
     *
     * @param EventFilterConditions $conditions Filter conditions to incorporate into the returned QueryBuilder
     * @return QueryBuilder
     * @throws \Exception
     */
    private function fetchEvents(EventFilterConditions $conditions): QueryBuilder {
        $qb = $this->em->createQueryBuilder();
        if($conditions->archived) {
            $qb->select('e')->from('HoneySens\app\models\entities\ArchivedEvent', 'e');
            if($conditions->user !== null || $conditions->divisionID !== null) {
                // Only JOIN with division if we require to either show events for a certain user or of a specific
                // division. Omitting this JOIN enables admin users (user == null) to fetch archived events for
                // already deleted divisions, which don't have a division associated with them anymore.
                $qb->join('e.division', 'd');
            }
        } else {
            $qb->select('e')
                ->from('HoneySens\app\models\entities\Event', 'e')
                ->join('e.sensor', 's')
                ->join('s.division', 'd');
        }
        if($conditions->user !== null) {
            $qb->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $conditions->user->getId());
        }
        if($conditions->lastID !== null) {
            $qb->andWhere('e.id > :lastid')
                ->setParameter('lastid', $conditions->lastID);
        }
        if($conditions->filter !== null) {
            $filters = $qb->expr()->orX();
            // Parse both source and comment fields against the filter string
            $filters->add($qb->expr()->like('e.source', ':filter'));
            $filters->add($qb->expr()->like('e.comment', ':filter'));
            $qb->setParameter('filter', '%'. $conditions->filter . '%');
            // Also try to parse the filter string into a date
            $date = false;
            try {
                $date = new \DateTime($conditions->filter);
            } catch (\Exception $e) {}
            if ($date) {
                $timestamp = $date->format('Y-m-d');
                $filters->add($qb->expr()->like('e.timestamp', ':timestamp'));
                $qb->setParameter('timestamp', $timestamp . '%');
            }
            $qb->andWhere($filters);
        }
        if($conditions->sortBy !== null && $conditions->sortOrder !== null) {
            $qb->orderBy('e.' . $conditions->sortBy, $conditions->sortOrder);
        } else {
            // Default behaviour: return timestamp-sorted events
            $qb->orderBy('e.timestamp', 'desc');
        }
        if($conditions->divisionID !== null) {
            $qb->andWhere('d.id = :division')
                ->setParameter('division', $conditions->divisionID);
        }
        if($conditions->sensorID !== null && !$conditions->archived) {
            // Sensor ID filtering is not possible for archived events
            $qb->andWhere('s.id = :sensor')
                ->setParameter('sensor', $conditions->sensorID);
        }
        if($conditions->classification !== null) {
            $qb->andWhere('e.classification = :classification')
                ->setParameter('classification', $conditions->classification);
        }
        if($conditions->status !== null) {
            $qb->andWhere('e.status IN (:status)')
                ->setParameter('status', $conditions->status, ArrayParameterType::INTEGER);
        }
        if($conditions->fromTS !== null) {
            $timestamp = new \DateTime('@' . $conditions->fromTS);
            $timestamp->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            $qb->andWhere('e.timestamp >= :fromTS')
                ->setParameter('fromTS', $timestamp);
        }
        if($conditions->toTS !== null) {
            $timestamp = new \DateTime('@' . $conditions->toTS);
            $timestamp->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            $qb->andWhere('e.timestamp <= :toTS')
                ->setParameter('toTS', $timestamp);
        }
        if($conditions->list !== null) {
            $qb->andWhere('e.id IN (:list)')
                ->setParameter('list', $conditions->list, ArrayParameterType::INTEGER);
        }
        return $qb;
    }

    /**
     * Updates the 'last_updates' table to indicate that there was an update to the sensors, allowing clients
     * such as the UI to update their associated data (e.g. new event counter).
     */
    private function updateSensorRepo() {
        $this->em->getConnection()->executeUpdate('UPDATE last_updates SET timestamp = NOW() WHERE table_name = "sensors"');
    }
}
