<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\exceptions\NotFoundException;
use Respect\Validation\Validator as V;

class Certs extends RESTResource {

    static function registerRoutes($app, $em, $services, $config, $messages) {
        $app->get('/api/certs(/:id)/', function($id = null) use ($app, $em, $services, $config, $messages) {
            $controller = new Certs($em, $services, $config);
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

        $app->delete('/api/certs/:id', function($id) use ($app, $em, $services, $config, $messages) {
            $controller = new Certs($em, $services, $config);
            $controller->delete($id);
            echo json_encode([]);
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
            $qb->join('c.sensor', 's')
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

    // TODO Evaluate use case for DELETE
	public function delete($id) {
		$this->assureAllowed('delete');
        // Validation
        V::intVal()->check($id);
        // Persistence
		$em = $this->getEntityManager();
		$cert = $em->getRepository('HoneySens\app\models\entities\SSLCert')->find($id);
        V::objectType()->check($cert);
        if($cert->getSensor()) $cert->getSensor()->setCert(null);
        $em->remove($cert);
        $em->flush();
	}
}