<?php
namespace HoneySens\app\controllers;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use HoneySens\app\models\entities\Event;
use HoneySens\app\models\entities\EventDetail;
use HoneySens\app\models\entities\EventPacket;
use HoneySens\app\models\entities\LogEntry;
use HoneySens\app\models\entities\Task;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\models\ServiceManager;
use HoneySens\app\models\Utils;
use NoiseLabs\ToolKit\ConfigParser\ConfigParser;
use phpseclib\File\X509;
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
            $data = json_decode($request);
            $controller->create($data, $config);
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

        $app->delete('/api/events/:id', function($id) use ($app, $em, $services, $config, $messages) {
            $controller = new Events($em, $services, $config);
            $criteria = array('userID' => $controller->getSessionUserID(), 'id' => $id);
            $controller->delete($criteria);
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
     * @throws \HoneySens\app\models\exceptions\ForbiddenException
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
     * Verifies the given sensor data and creates new events on the server.
     * Also applies matching filter rules and triggers notifications in case of critical events.
     * Classification is also done while creating the event, taking into consideration the submitted data.
     * The expected data structure is a JSON string. The JSON data has to be formatted as follows:
     * {
     *   "sensor": <sensor_id>
     *   "signature": <signature>
     *   "events": <events|base64>
     * }
     * The signature has to be valid for the base64 encoded events string.
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
     * @param \stdClass $data
     * @param ConfigParser $config
     * @return array
     * @throws BadRequestException
     */
    public function create($data, ConfigParser $config) {
        // No $this->assureAllowed() authentication here, because sensors don't authenticate via the API,
        // but are using certificates instead.

        // Basic attribute validation
        V::attribute('sensor', V::intVal())
            ->attribute('signature', V::stringType())
            ->attribute('events', V::stringType())
            ->check($data);
        // Decode events data
        try {
            $eventsData = base64_decode($data->events);
        } catch(\Exception $e) {
            throw new BadRequestException();
        }
        // Check sensor certificate validity
        $em = $this->getEntityManager();
        $sensor = $em->getRepository('HoneySens\app\models\entities\Sensor')->find($data->sensor);
        V::objectType()->check($sensor);
        $cert = $sensor->getCert();
        $x509 = new X509();
        $x509->loadCA(file_get_contents(APPLICATION_PATH . '/../data/CA/ca.crt'));
        $x509->loadX509($cert->getContent());
        if(!$x509->validateSignature()) throw new BadRequestException();
        // Check signature
        $check = openssl_verify($eventsData, base64_decode($data->signature), $cert->getContent());
        if(!$check) throw new BadRequestException();
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
        // Event forwarding
        if($config->getBoolean('syslog', 'enabled')) {
            $taskService = $this->getServiceManager()->get(ServiceManager::SERVICE_TASK);
            foreach($events as $event) {
                if($em->contains($event))
                    $taskService->enqueue(null, Task::TYPE_EVENT_FORWARDER, array('id' => $event->getId()));
            }
        }
        // Send mails for each incident
        $mailService = $this->getServiceManager()->get(ServiceManager::SERVICE_CONTACT);
        foreach($events as $event) {
            if($em->contains($event)) $mailService->sendIncident($config, $em, $event);
        }
        return $events;
    }

    /**
     * Updates one or multiple Event objects according to various criteria (see documentation for fetchEvents()).
     * Alternatively, a single id or multiple ids can be provided as selection criteria.
     * To simplify the refresh process on the client side, only status and comment fields can be updated.
     * If permissions settings require comments, each status flag update to anything other than "UNEDITED"
     * also required a comment to be present.
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
        V::oneOf(V::key('new_status'), V::key('new_comment'))->check($criteria);
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
                ->setParameter('status', $criteria['new_status']);
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
     * - userID: removes only events that belong to the user with the given id
     *
     * @param array $criteria
     */
    public function delete($criteria) {
        $this->assureAllowed('delete');
        $em = $this->getEntityManager();
        // If the key 'id' or 'ids' is present, just delete those individual IDs
        if(V::oneOf(V::key('id'), V::key('ids'))->validate($criteria)) {
            // DQL doesn't support joins in DELETE, so we collect the candidates first
            $qb = $em->createQueryBuilder();
            $qb->select('e')->from('HoneySens\app\models\entities\Event', 'e')->join('e.sensor', 's')->join('s.division', 'd');
            if (V::key('id', V::intVal())->validate($criteria)) {
                $qb->andWhere('e.id = :id')
                    ->setParameter('id', $criteria['id']);
            } else if (V::key('ids', V::arrayType())->validate($criteria)) {
                // We need at least one valid id
                V::notEmpty()->check($criteria['ids']);
                foreach($criteria['ids'] as $id) V::intVal()->check($id);
                $qb->andWhere('e.id IN (:ids)')
                    ->setParameter('ids', $criteria['ids'], Connection::PARAM_STR_ARRAY);
            }
            if (V::key('userID', V::intType())->validate($criteria)) {
                $qb->andWhere(':userid MEMBER OF d.users')
                    ->setParameter('userid', $criteria['userID']);
            }
            // Persistence
            $results = $qb->getQuery()->getResult();
            foreach ($results as $result) {
                $em->remove($result);
            }
            $em->flush();
        } else {
            // Batch delete events according to the remaining query parameters
            $qb = $this->fetchEvents($criteria);
            $qb->select('e.id');
            $eventIDs = $qb->getQuery()->getResult();
            if(sizeof($eventIDs) == 0) return;
            // Fetch EventDetail and EventPacket IDs associated with those events
            $eventDetailIDs = $em->createQueryBuilder()
                ->select('ed.id')->from('HoneySens\app\models\entities\EventDetail', 'ed') ->join('ed.event', 'e')
                ->where('e.id in (:ids)')->setParameter('ids', $eventIDs)
                ->getQuery()->getResult();
            $eventPacketIDs = $em->createQueryBuilder()
                ->select('ep.id')->from('HoneySens\app\models\entities\EventPacket', 'ep') ->join('ep.event', 'e')
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
            $em->createQueryBuilder()
                ->delete('HoneySens\app\models\entities\Event', 'e')
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
     *
     * If no criteria are given, all events will be selected matching the default parameters.
     *
     * @param array $criteria
     * @return array
     * @throws \Exception
     */
    private function fetchEvents($criteria) {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('e')->from('HoneySens\app\models\entities\Event', 'e')->join('e.sensor', 's')->join('s.division', 'd');
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
            // Parse both source and comment fields against the filter string
            $qb->orWhere('e.source LIKE :source')
                ->setParameter('source', '%'. $criteria['filter'] . '%');
            $qb->orWhere('e.comment LIKE :comment')
                ->setParameter('comment', '%'. $criteria['filter'] . '%');
            // Also try to parse the filter string into a date
            $date = false;
            try {
                $date = new \DateTime($criteria['filter']);
            } catch (\Exception $e) {}
            if ($date) {
                $timestamp = $date->format('Y-m-d');
                $qb->orWhere('e.timestamp LIKE :timestamp')
                    ->setParameter('timestamp', $timestamp . '%');
            }
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
        if(V::key('sensor', V::intVal())->validate($criteria)) {
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
     * Updates the 'last_updates' table to indicate that there was an update to the sensors, allowing clients
     * such as the UI to update their associated data (e.g. new event counter).
     *
     * @param EntityManager $em
     */
    private function updateSensorRepo(EntityManager $em) {
        $em->getConnection()->executeUpdate('UPDATE last_updates SET timestamp = NOW() WHERE table_name = "sensors"');
    }
}