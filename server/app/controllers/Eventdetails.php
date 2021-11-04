<?php
namespace HoneySens\app\controllers;

use Respect\Validation\Validator as V;

class Eventdetails extends RESTResource {

    static function registerRoutes($app, $em, $services, $config, $messages) {
        // Returns details (including packets) that belong to a certain event
        $app->get('/api/eventdetails/by-event/:id', function($id) use ($app, $em, $services, $config, $messages) {
            $controller = new Eventdetails($em, $services, $config);
            $details = $controller->get(array('userID' => $controller->getSessionUserID(), 'eventID' => $id, 'type' => 0));
            $packets = $controller->get(array('userID' => $controller->getSessionUserID(), 'eventID' => $id, 'type' => 1));
            echo json_encode(array('details' => $details, 'packets' => $packets));
        });

        $app->get('/api/eventdetails/by-archived-event/:id', function($id) use($app, $em, $services, $config, $messages) {
            $controller = new Eventdetails($em, $services, $config);
            echo json_encode($controller->getArchivedDetails($id, $controller->getSessionUserID()));
        });
    }

    /**
     * Fetches event details from the DB by various criteria:
     * - type: 0 for EventDetails, 1 for EventPackets
     * - userID: return only EventDetails/EventPackets that belong to the user with the given id
     * - eventID: return only EventDetails/EventPackets that belong to a certain event with the given id
     * - id: return the EventDetail object with the given id
     * If no criteria are given, all EventDetails/EventPackets are returned.
     *
     * @param array $criteria
     * @return array
     */
    public function get($criteria) {
        $this->assureAllowed('get');
        $qb = $this->getEntityManager()->createQueryBuilder();
        V::key('type', V::intType()->between(0, 1))->check($criteria);
        $entity = 'HoneySens\app\models\entities\EventDetail';
        if($criteria['type'] == 1) {
            $entity = 'HoneySens\app\models\entities\EventPacket';
        }
        $qb->select('entity')->from($entity, 'entity')->join('entity.event', 'e');
        if(V::key('userID', V::intType())->validate($criteria)) {
            $qb->join('e.sensor', 's')
                ->join('s.division', 'd')
                ->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $criteria['userID']);
        }
        if(V::key('eventID', V::intVal())->validate($criteria)) {
            $qb->andWhere('e.id = :eventid')
                ->setParameter('eventid', $criteria['eventID']);
        }
        if(V::key('id', V::intVal())->validate($criteria)) {
            $qb->andWhere('entity.id = :id')
                ->setParameter('id', $criteria['id']);
            return $qb->getQuery()->getSingleResult()->getState();
        } else {
            $details = array();
            foreach($qb->getQuery()->getResult() as $detail) {
                $details[] = $detail->getState();
            }
            return $details;
        }
    }

    /**
     * Fetches and returns event details from the event archive for a given archived event ID.
     *
     * @param int $eventID
     * @param int $userID
     * @return array
     * @throws \HoneySens\app\models\exceptions\ForbiddenException
     */
    public function getArchivedDetails($eventID, $userID=null) {
        $this->assureAllowed('get');
        V::intVal()->check($eventID);
        $qb = $this->getEntityManager()->createQueryBuilder()
            ->select('e')
            ->from('HoneySens\app\models\entities\ArchivedEvent', 'e');
        if($userID != null) {
            // Only join with division in case a user ID was provided. Otherwise this won't return results for
            // archived events without a division.
            $qb->join('e.division', 'd')
                ->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $userID);
        }
        $qb->andWhere('e.id = :id')
            ->setParameter('id', $eventID);
        $event = $qb->getQuery()->getSingleResult();
        V::objectType()->check($event);
        return array('details' => $event->getDetails(), 'packets' => $event->getPackets());
    }
}