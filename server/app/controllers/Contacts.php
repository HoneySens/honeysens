<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\exceptions\NotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;

/**
 * Class Contacts
 * @package HoneySens\app\controllers
 *
 * Contact creation and updates are handled by the division controller,
 * because contacts always belong to a certain division.
 */
class Contacts extends RESTResource {

    static function registerRoutes($app, $em, $services, $config) {
        $app->get('/api/contacts[/{id:\d+}]', function(Request $request, Response $response, array $args) use ($app, $em, $services, $config) {
            $controller = new Contacts($em, $services, $config);
            $criteria = array();
            $criteria['userID'] = $controller->getSessionUserID();
            $criteria['id'] = $args['id'] ?? null;
            try {
                $result = $controller->get($criteria);
            } catch(\Exception $e) {
                throw new NotFoundException();
            }
            $response->getBody()->write(json_encode($result));
            return $response;
        });
    }

    /**
     * Fetches IncidentContacts from the DB by various criteria:
     * - userID: return only contacts that belong to the user with the given id
     * - id: return the contact with the given id
     * If no criteria are given, all certs are returned.
     *
     * @param array $criteria
     * @return array
     */
    public function get($criteria) {
        $this->assureAllowed('get');
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('c')->from('HoneySens\app\models\entities\IncidentContact', 'c');
        if(V::key('userID', V::intType())->validate($criteria)) {
            $qb->join('c.division', 'd')
                ->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $criteria['userID']);
        }
        if(V::key('id', V::intVal())->validate($criteria)) {
            $qb->andWhere('c.id = :id')
                ->setParameter('id', $criteria['id']);
            return $qb->getQuery()->getSingleResult()->getState();
        } else {
            $contacts = array();
            foreach($qb->getQuery()->getResult() as $contact) {
                $contacts[] = $contact->getState();
            }
            return $contacts;
        }
    }
}
