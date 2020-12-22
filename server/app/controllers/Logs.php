<?php
namespace HoneySens\app\controllers;

use Respect\Validation\Validator as V;

class Logs extends RESTResource {

    static function registerRoutes($app, $em, $services, $config, $messages) {
        $app->get('/api/logs/', function() use ($app, $em, $services, $config, $messages) {
            $controller = new Logs($em, $services, $config);
            $criteria = $app->request->get();
            $result = $controller->get($criteria);
            echo json_encode($result);
        });
    }

    /**
     * Fetches log entries from the DB by various optional criteria:
     * - page: page number of result list (requires per_page), defaults to 0
     * - per_page: number of results per page (requires page), defaults to 15, max is 512
     * - user_id: returns only log entries for the given user id
     * - resource_type: returns only log entries of the given type
     * - resource_id: returns only log entries for the given resource id (requires resource_type)
     *
     * @param array $criteria
     * @return array
     * @throws \HoneySens\app\models\exceptions\ForbiddenException
     */
    public function get($criteria) {
        $this->assureAllowed('get');
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('COUNT(l.id)')->from('HoneySens\app\models\entities\LogEntry', 'l');
        if(V::key('user_id', V::intVal())->validate($criteria)) {
            $qb->andWhere('l.userID = :uid')
                ->setParameter('uid', $criteria['user_id']);
        }
        if(V::key('resource_type', V::intVal()->between(0, 12))->validate($criteria)) {
            $qb->andWhere('l.resourceType = :rtype')
                ->setParameter('rtype', $criteria['resource_type']);
        }
        if(V::key('resource_id', V::intVal())->validate($criteria)) {
            V::key('resource_type')->check($criteria);
            $qb->andWhere('l.resourceID = :rid')
                ->setParameter('rid', $criteria['resource_id']);
        }
        // Always order by timestamp
        $qb->orderBy('l.timestamp', 'desc');
        // Calculate the total number of results so far
        $totalCount = $qb->getQuery()->getSingleScalarResult();
        // Restrict and output in dependence of page params
        $qb->select('l');
        if(V::key('page', V::intVal())->key('per_page', V::intVal()->between(1, 512))->validate($criteria)) {
            $qb->setFirstResult($criteria['page'] * $criteria['per_page'])
                ->setMaxResults($criteria['per_page']);
        } else {
            // Default behaviour: return only the first x events
            $qb->setFirstResult(0)->setMaxResults(15);
        }
        $result = array();
        foreach($qb->getQuery()->getResult() as $log) $result[] = $log->getState();
        return array('items' => $result, 'total_count' => $totalCount);
    }
}