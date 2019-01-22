<?php
namespace HoneySens\app\controllers;

use Doctrine\DBAL\Connection;
use HoneySens\app\models\entities\Event;
use HoneySens\app\models\entities\EventDetail;
use HoneySens\app\models\entities\EventPacket;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\models\ServiceManager;
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
            $ids = json_decode($request);
            $criteria = array('ids' => $ids, 'userID' => $controller->getSessionUserID());
            $controller->delete($criteria);
            echo json_encode([]);
        });
    }
		
	/**
	 * Fetches events from the DB by various criteria:
     * - userID: return only events that belong to the user with the given id
     * - lastID: return only events that have a higher id than the given one
     * - id: return the event with the given id
     * - sort_by: event attribute name to sort after (only together with 'order')
     * - order: sort order ('asc' or 'desc'), only together with 'sort_by'
     * - division: Division id to limit results
     * - sensor: Sensor id to limit results
     * - classification: classification (int) to limit results (0 to 4)
     * - status: status (int) to limit results (0 to 3)
     * - page: page number of result list (only together with 'per_page'), default 0
     * - per_page: number of results per page (only together with 'page'), default 15, max 60
     *
     * If no criteria are given, all events are returned matching the default parameters.
	 *
	 * @param array $criteria
	 * @return array
	 */
	public function get($criteria) {
        $this->assureAllowed('get');
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
        if(V::key('sort_by', V::in(['timestamp', 'sensor', 'classification', 'source']))
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
        if(V::key('status', V::intVal()->between(0, 3))->validate($criteria)) {
            $qb->andWhere('e.status = :status')
                ->setParameter('status', $criteria['status']);
        }
        if(V::key('id', V::intVal())->validate($criteria)) {
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
            $events = array();
            foreach($qb->getQuery()->getResult() as $event) {
                $events[] = $event->getState();
            }
            return array('items' => $events, 'total_count' => $totalCount);
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
     * @param string $data
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
                if($filter->matches($event)) {
                    $em->remove($event);
                    $filter->addToCount(1);
                    break;
                }
            }
		}
		$em->flush();
		// Send mails for each incident
		$mailService = $this->getServiceManager()->get(ServiceManager::SERVICE_CONTACT);
		foreach($events as $event) {
            $mailService->sendIncident($config, $em, $event);
		}
		return $events;
	}

    /**
     * Updates one or multiple Event objects.
     * To simplify the refresh process on the client side, only status and comment fields can be updated.
     * The following parameters have to be provided:
     * - id: updates the event with the given ID
     * OR
     * - ids: array of multiple IDs to update
     *
     * - status: Status value, 0 to 3
     * AND/OR
     * - comment: Comment string
     *
     * Optional criteria:
     * - userID: Updates only events that belong to the user with the given id
     *
     * @param array $criteria
     */
    public function update($criteria) {
        $this->assureAllowed('update');
        // Validation, either 'id' or 'ids' must be present
        V::oneOf(V::key('id'), V::key('ids'))->check($criteria);
        V::oneOf(V::key('status'), V::key('comment'))->check($criteria);
        $em = $this->getEntityManager();

        // Doctrine doesn't support JOINs in UPDATE queries, therefore we first manually
        // preselect affected events with a separate query.
        // (see https://stackoverflow.com/questions/15293502/doctrine-query-builder-not-working-with-update-and-inner-join)
        $affectedEvents = array();
        $affectedEventsQb = $em->createQueryBuilder();
        $affectedEventsQb->select('e.id')->from('HoneySens\app\models\entities\Event', 'e')
            ->join('e.sensor', 's')
            ->join('s.division', 'd');
        if(V::key('id', V::intVal())->validate($criteria)) {
            $affectedEventsQb->andWhere('e.id = :id')
                ->setParameter('id', $criteria['id']);
        } else if(V::key('ids', V::arrayType())->validate($criteria)) {
            V::notEmpty()->check($criteria['ids']);
            foreach($criteria['ids'] as $id) V::intVal()->check($id);
            $affectedEventsQb->andWhere('e.id IN (:ids)')
                ->setParameter('ids', $criteria['ids'], Connection::PARAM_STR_ARRAY);
        }
        if(V::key('userID', V::intType())->validate($criteria)) {
            $affectedEventsQb->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $criteria['userID']);
        }
        foreach($affectedEventsQb->getQuery()->getResult() as $r) $affectedEvents[] = $r['id'];

        $qb = $em->createQueryBuilder();
        $qb->update('HoneySens\app\models\entities\Event', 'e')
            ->where('e.id IN (:ids)')
            ->setParameter('ids', $affectedEvents, Connection::PARAM_INT_ARRAY);
        if(V::key('status', V::intVal()->between(0, 3))->validate($criteria)) {
            $qb->set('e.status', ':status')
                ->setParameter('status', $criteria['status']);
        }
        if(V::key('comment', V::stringType())->validate($criteria)) {
            $qb->set('e.comment', ':comment')
                ->setParameter('comment', $criteria['comment']);
        }
        // Persistence
        $qb->getQuery()->execute();
    }

    /**
     * Removes one or multiple Event objects.
     * The following criteria have to be provided:
     * - id: deletes the event with the given ID
     * OR
     * - ids: array of multiple IDs to delete
     * Optional criteria:
     * - userID: removes only events that belong to the user with the given id
     *
     * @param array $criteria
     */
	public function delete($criteria) {
		$this->assureAllowed('delete');
        $em = $this->getEntityManager();
        // Validation, either 'id' or 'ids' must be present
        V::oneOf(V::key('id'), V::key('ids'))->check($criteria);
        // DQL doesn't support joins in DELETE, so we collect the candidates first
        $qb = $em->createQueryBuilder();
        $qb->select('e')->from('HoneySens\app\models\entities\Event', 'e')->join('e.sensor', 's')->join('s.division', 'd');
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
        // Persistence
        $results = $qb->getQuery()->getResult();
        foreach($results as $result) {
            $em->remove($result);
        }
        $em->flush();
	}

    public function test() {
        $em = $this->getEntityManager();
        $sensor = $em->getRepository('HoneySens\app\models\entities\Sensor')->find(2);
        for($i=0;$i<10;$i++) {
            $event = new Event();
            $event->setTimestamp(new \DateTime())
                ->setService('SSH')
                ->setSource('source')
                ->setSummary('summary')
                ->setSensor($sensor)
                ->setClassification(Event::CLASSIFICATION_CONN_ATTEMPT);
            $em->persist($event);
        }
        $em->flush();
    }
}