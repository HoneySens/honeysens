<?php
namespace HoneySens\app\services;

use Doctrine\ORM\EntityManager;
use HoneySens\app\models\entities\LogEntry;
use HoneySens\app\models\entities\User;
use Respect\Validation\Validator as V;

class LogService {

    private $em;

    public function __construct(EntityManager $em) {
        $this->em = $em;
    }

    public function isEnabled() {
        return getenv('API_LOG') === 'true';
    }

    /**
     * Records a log entry, references the session user by default.
     */
    public function log($message, $resourceType, $resourceID=null, $userID=null) {
        if($this->isEnabled()) {
            $logEntry = new LogEntry();
            $logEntry->setTimestamp(new \DateTime())
                ->setMessage($message)
                ->setResourceType($resourceType)
                ->setResourceID($resourceID)
                ->setUserID($userID ?? $this->getSessionUserID());
            $this->em->persist($logEntry);
            $this->em->flush();
        }
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
        $qb = $this->em->createQueryBuilder();
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

    private function getSessionUserID() {
        if($_SESSION['user']['role'] == User::ROLE_GUEST) return null;
        $user = $this->em->getRepository('HoneySens\app\models\entities\User')->find($_SESSION['user']['id']);
        return $user?->getId();
    }
}