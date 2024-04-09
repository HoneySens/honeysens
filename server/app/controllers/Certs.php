<?php
namespace HoneySens\app\controllers;

use Doctrine\ORM\Query\Expr\Join as Join;
use HoneySens\app\models\entities\Sensor;
use HoneySens\app\models\exceptions\NotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;

class Certs extends RESTResource {

    static function registerRoutes($app, $em, $services, $config) {
        $app->get('/api/certs/{id:\d+}', function(Request $request, Response $response, array $args) use ($app, $em, $services, $config) {
            $controller = new Certs($em, $services, $config);
            $criteria = array();
            $criteria['userID'] = $controller->getSessionUserID();
            $criteria['id'] = $args['id'];
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
     * Fetches SSLCerts from the DB by various criteria:
     * - userID: return only certs that belong to the user with the given id
     * - id: return the cert with the given id
     * If no criteria are given, all certs are returned.
     *
     * @param array $criteria
     * @return array
     * @throws \HoneySens\app\models\exceptions\ForbiddenException
     */
    public function get($criteria) {
        $this->assureAllowed('get');
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('c')->from('HoneySens\app\models\entities\SSLCert', 'c');
        if(V::key('userID', V::intType())->validate($criteria)) {
            $qb->join(Sensor::class, 's', Join::WITH, 's.EAPOLCACert = c')
                ->join('s.division', 'd')
                ->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $criteria['userID']);
        }
        if(V::key('id', V::intVal())->validate($criteria)) {
            $qb->andWhere('c.id = :id')
                ->setParameter('id', $criteria['id']);
            return $qb->getQuery()->getSingleResult()->getState();
        } else {
            $certs = array();
            foreach($qb->getQuery()->getResult() as $cert) {
                $certs[] = $cert->getState();
            }
            return $certs;
        }
    }
}
