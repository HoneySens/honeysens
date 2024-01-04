<?php
namespace HoneySens\app\controllers;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use HoneySens\app\models\entities\ArchivedEvent;
use HoneySens\app\models\entities\Event;
use HoneySens\app\models\entities\EventDetail;
use HoneySens\app\models\entities\EventPacket;
use HoneySens\app\models\entities\LogEntry;
use HoneySens\app\models\entities\Task;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\models\ServiceManager;
use HoneySens\app\models\Utils;
use NoiseLabs\ToolKit\ConfigParser\ConfigParser;
use Respect\Validation\Validator as V;

class Events extends RESTResource {

    static function registerRoutes($app, $em, $services, $config, $messages) {
        $app->get('/api/events(/:id)/', function($id = null) use ($app, $em, $services, $config, $messages) {
            $controller = new Events($em, $services, $config);
            $criteria = $app->request->get();
            $criteria['userID'] = $controller->getSessionUserID();
            $criteria['id'] = $id;
            try {
                $result = $controller->get($criteria);
            } catch(\Exception $e) {
                throw new NotFoundException();
            }
            echo json_encode($result);
        });

        $app->post('/api/events', function() use ($app, $em, $services, $config, $messages) {
            $controller = new Events($em, $services, $config);
            $request = $app->request()->getBody();
            V::json()->check($request);
            $eventData = json_decode($request);
            $sensor = $controller->validateSensorRequest('create', $request);
            $controller->create($sensor, $eventData, $config, $app->log);
            $controller->setMACHeaders($sensor, 'create');
        });

        $app->put('/api/events/:id', function($id) use ($app, $em, $services, $config, $messages) {
            $controller = new Events($em, $services, $config);
            $request = $app->request()->getBody();
            V::json()->check($request);
            $eventData = json_decode($request, true);
            $eventData['id'] = $id;
            $eventData['userID'] = $controller->getSessionUserID();
            $controller->update($eventData);
            echo json_encode([]);
        });

        $app->put('/api/events', function() use ($app, $em, $services, $config, $messages) {
            $controller = new Events($em, $services, $config);
            $request = $app->request()->getBody();
            V::json()->check($request);
            $eventData = json_decode($request, true);
            $eventData['userID'] = $controller->getSessionUserID();
            $controller->update($eventData);
            echo json_encode([]);
        });

        $app->delete('/api/events', function() use ($app, $em, $services, $config, $messages) {
            $controller = new Events($em, $services, $config);
            $request = $app->request()->getBody();
            V::json()->check($request);
            $criteria = json_decode($request, true);
            $criteria['userID'] = $controller->getSessionUserID();
            $controller->delete($criteria);
            echo json_encode([]);
        });
    }

    /**
     * Fetches events from the DB by various criteria (see documentation for fetchEvents()).
     * In addition to those, the final output format is determined by the following criteria keys:
     * - id: return just the event with the given id
     * - format: essentially the ACCEPT header of the HTTP request, defines the intended output format
     * - page: page number of result list (only together with 'per_page'), default 0
     * - per_page: number of results per page (only together with 'page'), default 15, max 512
     *
     * If no criteria are given, all events are returned matching the default parameters.
     *
     * @param array $criteria
     * @return array
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\Query\QueryException
     * @throws ForbiddenException
     * @throws \Exception
     */
    public function get($criteria) {
        $this->assureAllowed('get');
        $qb = $this->fetchEvents($criteria);
        if(V::key('id', V::intVal())->validate($criteria)) {
            // Single event output, ignores the format parameter
            $qb->andWhere('e.id = :id')
                ->setParameter('id', $criteria['id']);
            return $qb->getQuery()->getSingleResult()->getState();
        } else {
            // Calculate the total number of results by altering the query
            $qb->select('COUNT(e.id)');
            $totalCount = $qb->getQuery()->getSingleScalarResult();
            // Restrict the result
            $qb->select('e');
            if(V::key('page', V::intVal())->key('per_page', V::intVal()->between(1, 512))->validate($criteria)) {
                $qb->setFirstResult($criteria['page'] * $criteria['per_page'])
                    ->setMaxResults($criteria['per_page']);
            } else {
                // Default behaviour: return only the first x events
                $qb->setFirstResult(0)->setMaxResults(15);
            }

            // Output depends on the requested format
            if(V::key('format', V::stringType())->validate($criteria) && $criteria['format'] == 'text/csv') {
                $qb->setFirstResult(0)->setMaxResults($totalCount);
                $taskParams = array('query' => Utils::getFullSQL($qb->getQuery()));
                $task = $this->getServiceManager()->get(ServiceManager::SERVICE_TASK)->enqueue($this->getSessionUser(), Task::TYPE_EVENT_EXTRACTOR, $taskParams);
                return $task->getState();
            } else {
                $events = array();
                foreach($qb->getQuery()->getResult() as $event) {
                    $events[] = $event->getState();
                }
                return array('items' => $events, 'total_count' => $totalCount);
            }
        }
    }

    /**
     * Verifies the given event data and creates new events on the server.
     * Also applies matching filter rules and triggers notifications in case of critical events.
     * Classification is also done while creating the event, taking into consideration the submitted data.
     * The expected data structure is a JSON string. The JSON data has to be formatted as follows:
     * {
     *   "events": <events|base64>
     * }
     *
     * The base64 encoded events data has to be another JSON string formatted as follows:
     * [{
     *   "timestamp": <timestamp>,
     *   "service": <service>,
     *   "source": <source>,
     *   "summary": <summary>,
     *   "details": [{
     *     "timestamp": <timestamp>|null,
     *     "type": <type>,
     *     "data": <data>
     *   }, ...],
     *   "packets": [{
     *     "timestamp": <timestamp>,
     *     "protocol": <protocol>,
     *     "port": <port>,
     *     "headers": [{
     *       <field>: <value>
     *     }, ...],
     *     "payload": <payload|base64>
     *   }, ...}
     * }, ...]
     *
     * The method returns an array of all the Event objects that were created.
     *
     * @param Sensor $sensor
     * @param \stdClass $data
     * @param ConfigParser $config
     * @param \Slim\Log $log
     * @return array
     * @throws BadRequestException
     */
    public function create($sensor, $data, ConfigParser $config, \Slim\Log $log) {
        // Basic attribute validation
        V::attribute('events', V::stringType())->check($data);
        // Decode events data
        try {
            $eventsData = base64_decode($data->events);
        } catch(\Exception $e) {
            throw new BadRequestException();
        }
        $em = $this->getEntityManager();
        // Create events
        try {
            $eventsData = json_decode($eventsData);
        } catch(\Exception $e) {
            throw new BadRequestException();
        }
        // Data segment validation
        V::arrayVal()
            ->each(V::objectType()
                ->attribute('timestamp', V::intVal())
                ->attribute('details', V::arrayVal()->each(
                    V::objectType()
                    ->attribute('timestamp', V::intVal())
                    ->attribute('type', V::intVal()->between(0, 1))
                    ->attribute('data', V::stringType())))
                ->attribute('packets', V::arrayVal()->each(
                    V::objectType()
                    ->attribute('timestamp', V::intVal())
                    ->attribute('protocol', V::intVal()->between(0, 2))
                    ->attribute('port', V::intVal()->between(0, 65535))
                    ->attribute('payload', V::optional(V::stringType()))
                    ->attribute('headers', V::arrayVal())))
                ->attribute('service', V::intVal())
                ->attribute('source', V::stringType())
                ->attribute('summary', V::stringType()))
            ->check($eventsData);
        // Persistence
        $events = array();
        foreach($eventsData as $eventData) {
            // TODO make optional fields optional (e.g. packets and details)
            $timestamp = new \DateTime('@' . $eventData->timestamp);
            $timestamp->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            $event = new Event();
            // Save event details
            $details = array();
            foreach($eventData->details as $detailData) {
                if($detailData->timestamp === null) {
                    $detailTimestamp = null;
                } else {
                    $detailTimestamp = new \DateTime('@' . $detailData->timestamp);
                    $detailTimestamp->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                }
                $eventDetail = new EventDetail();
                $eventDetail->setTimestamp($detailTimestamp)
                    ->setType($detailData->type)
                    ->setData($detailData->data);
                $event->addDetails($eventDetail);
                $em->persist($eventDetail);
                $details[] = $eventDetail;
            }
            // Save event packets
            $packets = array();
            foreach($eventData->packets as $packetData) {
                $eventPacket = new EventPacket();
                $timestamp = new \DateTime('@' . $packetData->timestamp);
                $timestamp->setTimezone(new \DateTimeZone(date_default_timezone_get()));
                $eventPacket->setTimestamp($timestamp)
                    ->setProtocol($packetData->protocol)
                    ->setPort($packetData->port)
                    ->setPayload($packetData->payload);
                foreach($packetData->headers as $field => $value) {
                    $eventPacket->addHeader($field, $value);
                }
                $event->addPacket($eventPacket);
                $em->persist($eventPacket);
                $packets[] = $eventPacket;
            }
            // Save remaining event data
            $event->setTimestamp($timestamp)
                ->setService($eventData->service)
                ->setSource($eventData->source)
                ->setSummary($eventData->summary)
                ->setSensor($sensor);
            // Do classification
            // TODO be more sophisticated here than simply matching service and classification
            switch($event->getService()) {
                case Event::SERVICE_RECON:
                    if($event->getSummary() == 'Scan') $event->setClassification(Event::CLASSIFICATION_PORTSCAN);
                    else $event->setClassification(Event::CLASSIFICATION_CONN_ATTEMPT);
                    break;
                case Event::SERVICE_DIONAEA:
                case Event::SERVICE_KIPPO:
                    $event->setClassification(Event::CLASSIFICATION_LOW_HP);
                    break;
                default:
                    $event->setClassification(Event::CLASSIFICATION_UNKNOWN);
            }
            $em->persist($event);
            $events[] = $event;
        }
        // Apply filters
        $filters = $sensor->getDivision()->getEventFilters();
        foreach($events as $event) {
            foreach($filters as $filter) {
                if($filter->isEnabled() && $filter->matches($event)) {
                    $em->remove($event);
                    $filter->addToCount(1);
                    break;
                }
            }
        }
        $em->flush();
        // New events should also refresh the sensors repo so that dependent UI data (e.g. new event cnt) can be updated
        if($em->getUnitOfWork()->size() > 0) $this->updateSensorRepo($em);
        try {
            // Event forwarding
            if ($config->getBoolean('syslog', 'enabled')) {
                $taskService = $this->getServiceManager()->get(ServiceManager::SERVICE_TASK);
                foreach ($events as $event) {
                    if ($em->contains($event))
                        $taskService->enqueue(null, Task::TYPE_EVENT_FORWARDER, array('id' => $event->getId()));
                }
            }
            // Send mails for each incident
            $mailService = $this->getServiceManager()->get(ServiceManager::SERVICE_CONTACT);
            foreach ($events as $event) {
                if ($em->contains($event)) $mailService->sendIncident($config, $em, $event);
            }
        } catch(\Exception $e) {
            // If subsequent event handlers cause exceptions, fail gracefully and signal successful event creation to sensor
            $log->error('Exception during post-event handling');
            $log->error($e);
        }
        return $events;
    }

    /**
     * Updates one or multiple Event objects according to various criteria (see documentation for fetchEvents()).
     * Alternatively, a single id or multiple ids can be provided as selection criteria.
     * To simplify the refresh process on the client side, only status and comment fields can be updated.
     * If permissions settings require comments, each status flag update to anything other than "UNEDITED"
     * also requires a comment to be present.
     *
     * At least one of the following parameters have to be set to update the event model:
     * - new_status: Status value, 0 to 3
     * AND/OR
     * - new_comment: Comment string
     *
     * Optional criteria:
     * - userID: Updates only events that belong to the user with the given id
     *
     * @param array $criteria
     */
    public function update($criteria) {
        $this->assureAllowed('update');
        $em = $this->getEntityManager();
        V::anyOf(V::key('new_status'), V::key('new_comment'))->check($criteria);
        // Prevent modification of archived events
        if(V::key('archived', V::trueVal())->validate($criteria)) throw new BadRequestException();
        // If the key 'id' or 'ids' is present, just update those individual IDs
        if(V::oneOf(V::key('id'), V::key('ids'))->validate($criteria)) {
            // Doctrine doesn't support JOINs in UPDATE queries, therefore we first manually
            // preselect affected events with a separate query.
            // (see https://stackoverflow.com/questions/15293502/doctrine-query-builder-not-working-with-update-and-inner-join)
            $qb = $em->createQueryBuilder();
            $qb->select('e.id')->from('HoneySens\app\models\entities\Event', 'e')->join('e.sensor', 's')->join('s.division', 'd');
            if(V::key('id', V::intVal())->validate($criteria)) {
                $qb->andWhere('e.id = :id')
                    ->setParameter('id', $criteria['id']);
            } else if(V::key('ids', V::arrayType())->validate($criteria)) {
                // We need at least one valid id
                V::notEmpty()->check($criteria['ids']);
                foreach($criteria['ids'] as $id) V::intVal()->check($id);
                $qb->andWhere('e.id IN (:ids)')
                    ->setParameter('ids', $criteria['ids'], Connection::PARAM_STR_ARRAY);
            }
            if(V::key('userID', V::intType())->validate($criteria)) {
                $qb->andWhere(':userid MEMBER OF d.users')
                    ->setParameter('userid', $criteria['userID']);
            }
        } else {
            // Batch update events according to the remaining query parameters
            $qb = $this->fetchEvents($criteria);
            $qb->select('e.id');
        }
        $eventIDs = $qb->getQuery()->getResult();
        if(sizeof($eventIDs) == 0) return;
        // Persistence
        $qb = $em->createQueryBuilder()
            ->update('HoneySens\app\models\entities\Event', 'e')
            ->where('e.id IN (:ids)')->setParameter('ids', $eventIDs);
        if(V::key('new_status', V::intVal()->between(0, 3))->validate($criteria)) {
            if(V::intVal()->between(1, 3)->validate($criteria['new_status']) && $this->getConfig()->getBoolean('misc', 'require_event_comment'))
                V::key('new_comment', V::stringType()->length(1, 65535))->check($criteria);
            $qb->set('e.status', ':status')
                ->set('e.lastModificationTime', ':lastmod')
                ->setParameter('status', $criteria['new_status'])
                ->setParameter('lastmod', new \DateTime());
        }
        if(V::key('new_comment', V::stringType()->length(0, 65535))->validate($criteria)) {
            $qb->set('e.comment', ':comment')
                ->setParameter('comment', $criteria['new_comment']);
        }
        $qb->getQuery()->execute();
        $this->updateSensorRepo($em);
        $this->log('Metadata for one or multiple events updated', LogEntry::RESOURCE_EVENTS);
    }

    /**
     * Removes one or multiple Event objects according to various criteria (see documentation for fetchEvents()).
     * Alternatively, a single id or multiple ids can be provided as deletion criteria.
     *
     * Optional criteria:
     * - archive: after deletion, send the event to the archive
     * - userID: removes only events that belong to the user with the given id
     *
     * @param array $criteria
     * @throws BadRequestException
     * @throws ForbiddenException
     */
    public function delete($criteria) {
        $em = $this->getEntityManager();
        // In case the current user can't delete events, force archiving
        try {
            $this->assureAllowed('delete');
            $archive = V::key('archive', V::boolType())->validate($criteria) && $criteria['archive'];
        } catch (\Exception $e) {
            $this->assureAllowed('archive');
            $archive = true;
        }
        $archived = V::key('archived', V::boolType())->validate($criteria) && $criteria['archived'];
        // We can't archive already archived events
        if($archive && $archived) throw new BadRequestException();
        $entity = $archived ? 'HoneySens\app\models\entities\ArchivedEvent' : 'HoneySens\app\models\entities\Event';
        // If the key 'id' or 'ids' is present, just delete those individual IDs
        if(V::oneOf(V::key('id'), V::key('ids'))->validate($criteria)) {
            // DQL doesn't support joins in DELETE, so we collect the candidates first
            $qb = $em->createQueryBuilder();
            if($archived) {
                $qb->select('e')->from($entity, 'e')->join('e.division', 'd');
            } else {
                $qb->select('e')->from($entity, 'e')->join('e.sensor', 's')->join('s.division', 'd');
            }
            if(V::key('id', V::intVal())->validate($criteria)) {
                $qb->andWhere('e.id = :id')
                    ->setParameter('id', $criteria['id']);
            } else if (V::key('ids', V::arrayType())->validate($criteria)) {
                // We need at least one valid id
                V::notEmpty()->check($criteria['ids']);
                foreach($criteria['ids'] as $id) V::intVal()->check($id);
                $qb->andWhere('e.id IN (:ids)')
                    ->setParameter('ids', $criteria['ids'], Connection::PARAM_STR_ARRAY);
            }
            if(V::key('userID', V::intType())->validate($criteria)) {
                $qb->andWhere(':userid MEMBER OF d.users')
                    ->setParameter('userid', $criteria['userID']);
            }
            // Persistence
            $results = $qb->getQuery()->getResult();
            if($archive) {
                $eventIDs = array_map(function($e) { return $e->getId();}, $results);
                $this->archiveEvents($em, $eventIDs, true);
            } else {
                foreach ($results as $result) $em->remove($result);
                $em->flush();
            }
        } else {
            // Batch delete events according to the remaining query parameters
            $qb = $this->fetchEvents($criteria);
            $qb->select('e.id');
            $eventIDs = $qb->getQuery()->getResult();
            if(sizeof($eventIDs) == 0) return;
            if($archive) {
                $this->archiveEvents($em, $eventIDs, false);
            }
            if(!$archived) {
                // Fetch EventDetail and EventPacket IDs associated with those events
                $eventDetailIDs = $em->createQueryBuilder()
                    ->select('ed.id')->from('HoneySens\app\models\entities\EventDetail', 'ed')->join('ed.event', 'e')
                    ->where('e.id in (:ids)')->setParameter('ids', $eventIDs)
                    ->getQuery()->getResult();
                $eventPacketIDs = $em->createQueryBuilder()
                    ->select('ep.id')->from('HoneySens\app\models\entities\EventPacket', 'ep')->join('ep.event', 'e')
                    ->where('e.id in (:ids)')->setParameter('ids', $eventIDs)
                    ->getQuery()->getResult();
                // Manual delete cascade
                if(sizeof($eventDetailIDs) > 0)
                    $em->createQueryBuilder()
                        ->delete('HoneySens\app\models\entities\EventDetail', 'ed')
                        ->where('ed.id in (:ids)')->setParameter('ids', $eventDetailIDs)
                        ->getQuery()->execute();
                if(sizeof($eventPacketIDs))
                    $em->createQueryBuilder()
                        ->delete('HoneySens\app\models\entities\EventPacket', 'ep')
                        ->where('ep.id in (:ids)')->setParameter('ids', $eventPacketIDs)
                        ->getQuery()->execute();
            }
            $em->createQueryBuilder()
                ->delete($entity, 'e')
                ->where('e.id in (:ids)')->setParameter('ids', $eventIDs)
                ->getQuery()
                ->execute();
        }
        $this->updateSensorRepo($em);
        $this->log('One or multiple events deleted', LogEntry::RESOURCE_EVENTS);
    }

    /**
     * Returns a QueryBuilder that selects events from the DB according to various criteria:
     * - userID: include only events that belong to the user with the given id
     * - lastID: include only events that have a higher id than the given one
     * - sort_by: event attribute name to sort after (only together with 'order')
     * - order: sort order ('asc' or 'desc'), only together with 'sort_by'
     * - division: Division id to limit results
     * - sensor: Sensor id to limit results
     * - classification: classification (int) to limit results (0 to 4)
     * - status: status values (int array) to limit results (0 to 3)
     * - fromTS: timestamp, to specify the beginning of a date range
     * - toTS: timestamp, to specify the end of a date range
     * - list: list of requested event ids
     * - filter: search term to find events that contain the given string
     * - archived: whether to fetch from archived events (true, false or a string)
     *
     * If no criteria are given, all events will be selected matching the default parameters.
     *
     * @param array $criteria
     * @return array
     * @throws \Exception
     */
    private function fetchEvents($criteria) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $archived = V::key('archived', V::trueVal())->validate($criteria) && $criteria['archived'];
        if($archived) {
            $qb->select('e')->from('HoneySens\app\models\entities\ArchivedEvent', 'e');
            if(V::key('userID', V::intType())->validate($criteria) || V::key('division', V::intVal())->validate($criteria)) {
                // Only JOIN with division if we require to either show events for a certain userID or of a specific
                // division. Omitting this JOIN enables admin users (userID == null) to fetch archived events for
                // already deleted divisions, which don't have a division associated with them anymore.
                $qb->join('e.division', 'd');
            }
        } else {
            $qb->select('e')->from('HoneySens\app\models\entities\Event', 'e')->join('e.sensor', 's')->join('s.division', 'd');
        }
        if(V::key('userID', V::intType())->validate($criteria)) {
            $qb->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $criteria['userID']);
        }
        // TODO lastID is only part of the criteria submitted by the state controller, regular requests use last_id and are ignored
        if(V::key('lastID', V::intVal())->validate($criteria)) {
            $qb->andWhere('e.id > :lastid')
                ->setParameter('lastid', $criteria['lastID']);
        }
        if(V::key('filter', V::stringType())->validate($criteria)) {
            $filters = $qb->expr()->orX();
            // Parse both source and comment fields against the filter string
            $filters->add($qb->expr()->like('e.source', ':filter'));
            $filters->add($qb->expr()->like('e.comment', ':filter'));
            $qb->setParameter('filter', '%'. $criteria['filter'] . '%');
            // Also try to parse the filter string into a date
            $date = false;
            try {
                $date = new \DateTime($criteria['filter']);
            } catch (\Exception $e) {}
            if ($date) {
                $timestamp = $date->format('Y-m-d');
                $filters->add($qb->expr()->like('e.timestamp', ':timestamp'));
                $qb->setParameter('timestamp', $timestamp . '%');
            }
            $qb->andWhere($filters);
        }
        if(V::key('sort_by', V::in(['id', 'sensor', 'timestamp', 'classification', 'source', 'summary', 'status', 'comment']))
            ->key('order', V::in(['asc', 'desc']))
            ->validate($criteria)) {
            $qb->orderBy('e.' . $criteria['sort_by'], $criteria['order']);
        } else {
            // Default behaviour: return timestamp-sorted events
            $qb->orderBy('e.timestamp', 'desc');
        }
        if(V::key('division', V::intVal())->validate($criteria)) {
            $qb->andWhere('d.id = :division')
                ->setParameter('division', $criteria['division']);
        }
        if(V::key('sensor', V::intVal())->validate($criteria) && !$archived) {
            // Sensor ID filtering is not possible for archived events
            $qb->andWhere('s.id = :sensor')
                ->setParameter('sensor', $criteria['sensor']);
        }
        if(V::key('classification', V::intVal()->between(0, 4))->validate($criteria)) {
            $qb->andWhere('e.classification = :classification')
                ->setParameter('classification', $criteria['classification']);
        }
        if(V::key('status', V::stringType())->validate($criteria)) {
            if(strpos($criteria['status'], ',') !== false) {
                $status = explode(',', $criteria['status']);
                V::arrayVal()->each(V::intVal()->between(0, 3))->check($status);
            } else {
                V::intVal()->between(0, 3)->check($criteria['status']);
                $status = array($criteria['status']);
            }
            $qb->andWhere('e.status IN (:status)')
                ->setParameter('status', $status, Connection::PARAM_INT_ARRAY);
        }
        if(V::key('fromTS', V::intVal())->validate($criteria)) {
            $timestamp = new \DateTime('@' . $criteria['fromTS']);
            $timestamp->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            $qb->andWhere('e.timestamp >= :fromTS')
                ->setParameter('fromTS', $timestamp);
        }
        if(V::key('toTS', V::intVal())->validate($criteria)) {
            $timestamp = new \DateTime('@' . $criteria['toTS']);
            $timestamp->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            $qb->andWhere('e.timestamp <= :toTS')
                ->setParameter('toTS', $timestamp);
        }
        if(V::key('list', V::arrayVal()->each(V::intVal()))->validate($criteria)) {
            $qb->andWhere('e.id IN (:list)')
                ->setParameter('list', $criteria['list'], Connection::PARAM_INT_ARRAY);
        }
        return $qb;
    }

    /**
     * Sends the given events (by ID) to the archive and removes them from the primary event list.
     *
     * @param EntityManager $em
     * @param $eventIDs
     */
    public function archiveEvents(EntityManager $em, $eventIDs, $delete=false) {
        if(sizeof($eventIDs) == 0) return;
        foreach($em->createQueryBuilder()->select('e')->from('HoneySens\app\models\entities\Event', 'e')
                    ->where('e.id IN (:ids)')
                    ->setParameter('ids', $eventIDs)->getQuery()->getResult() as $event) {
            $archivedEvent = new ArchivedEvent($event);
            $em->persist($archivedEvent);
            if($delete) $em->remove($event);
            $em->flush();
        }
        $this->log('One or multiple events archived', LogEntry::RESOURCE_EVENTS);
    }

    /**
     * Updates the 'last_updates' table to indicate that there was an update to the sensors, allowing clients
     * such as the UI to update their associated data (e.g. new event counter).
     *
     * @param EntityManager $em
     */
    private function updateSensorRepo(EntityManager $em) {
        $em->getConnection()->executeUpdate('UPDATE last_updates SET timestamp = NOW() WHERE table_name = "sensors"');
    }
}