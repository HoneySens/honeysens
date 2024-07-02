<?php
namespace HoneySens\app\services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\OptimisticLockException;
use HoneySens\app\models\constants\LogResource;
use HoneySens\app\models\entities\LogEntry;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\models\exceptions\SystemException;

class LogService extends Service {

    public function isEnabled(): bool {
        return getenv('API_LOG') === 'true';
    }

    /**
     * Records a log message.
     *
     * @param string $message Message content
     * @param LogResource $resourceType REST resource that logs the message
     * @param int|null $resourceID Optional resource ID (of the given resource type) that is associated with the message
     * @param int|null $userID The user who records the log entry. If not set, records as the session user.
     * @throws SystemException
     */
    public function log(string $message, LogResource $resourceType, ?int $resourceID = null, ?int $userID = null): void {
        if(!$this->isEnabled()) return;
        $logEntry = new LogEntry();
        $logEntry->setTimestamp(new \DateTime())
            ->setMessage($message)
            ->setResourceType($resourceType)
            ->setResourceID($resourceID)
            ->setUserID($userID ?? $this->getSessionUserID());
        try {
            $this->em->persist($logEntry);
            $this->em->flush();
        } catch(ORMException|OptimisticLockException $e) {
            throw new SystemException($e);
        }
    }

    /**
     * Fetches log entries from the DB.
     *
     * @param int|null $userID Returns only log entries for the given user id
     * @param LogResource|null $resource Returns only log entries of the given type
     * @param int|null $resourceId Returns only log entries for the given resource id (requires $resource)
     * @param int $page Page number of result list (only together with $perPage)
     * @param int $perPage Number of results per page (only together with $page)
     * @throws NotFoundException
     */
    public function get(?int $userID = null, ?LogResource $resource = null, ?int $resourceId = null, int $page = 0, int $perPage = 15): array {
        $qb = $this->em->createQueryBuilder();
        $qb->select('COUNT(l.id)')->from('HoneySens\app\models\entities\LogEntry', 'l');
        if($userID !== null) {
            $qb->andWhere('l.userID = :uid')
                ->setParameter('uid', $userID);
        }
        if($resource !== null) {
            $qb->andWhere('l.resourceType = :rtype')
                ->setParameter('rtype', $resource->value);
        }
        if($resourceId !== null) {
            $qb->andWhere('l.resourceID = :rid')
                ->setParameter('rid', $resourceId);
        }
        // Always order by timestamp
        $qb->orderBy('l.timestamp', 'desc');
        try {
            // Calculate the total number of results so far
            $totalCount = $qb->getQuery()->getSingleScalarResult();
            // Restrict and output in dependence of page params
            $qb->select('l');
            $qb->setFirstResult($page * $perPage)
                ->setMaxResults($perPage);
            $result = array();
            foreach ($qb->getQuery()->getResult() as $log)
                $result[] = $log->getState();
            return array('items' => $result, 'total_count' => $totalCount);
        } catch(NonUniqueResultException|NoResultException) {
            throw new NotFoundException();
        }
    }

    /**
     * Returns the current session user ID or null in case no user is logged in.
     *
     * @return int|null
     * @throws SystemException
     */
    private function getSessionUserID(): ?int {
        if($_SESSION['user']['role'] == User::ROLE_GUEST) return null;
        try {
            $user = $this->em->getRepository('HoneySens\app\models\entities\User')->find($_SESSION['user']['id']);
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        return $user?->getId();
    }
}