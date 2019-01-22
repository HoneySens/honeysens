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
}
