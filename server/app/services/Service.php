<?php
namespace HoneySens\app\services;

use Doctrine\ORM\EntityManager;
use HoneySens\app\models\exceptions\ForbiddenException;

abstract class Service {

    protected EntityManager $em;

    public function __construct(EntityManager $em) {
        $this->em = $em;
    }

    /**
     * Asserts that a given user is associated with a specific division.
     * Throws an exception in case that affiliation doesn't exist.
     *
     * @param int $divisionID Division ID to check association for
     * @param int $userID User ID to check association for
     * @throws ForbiddenException
     */
    public function assureUserAffiliation(int $divisionID, int $userID): void {
        $qb = $this->em->createQueryBuilder();
        $qb->select('d')->from('HoneySens\app\models\entities\Division', 'd')
            ->where('d.id = :id')
            ->andwhere(':userid MEMBER OF d.users')
            ->setParameter('id', $divisionID)
            ->setParameter('userid', $userID);
        try {
            $qb->getQuery()->getSingleResult();
        } catch(\Exception) {
            throw new ForbiddenException();
        }
    }
}