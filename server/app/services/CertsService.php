<?php
namespace HoneySens\app\services;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\Query\Expr\Join as Join;
use HoneySens\app\models\constants\UserRole;
use HoneySens\app\models\entities\Sensor;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\NotFoundException;

class CertsService extends Service {

    /**
     * Fetches SSLCerts from the DB.
     *
     * @param User $user User for which to retrieve associated entities; admins receive all entities
     * @param int|null $id ID of a specific cert to fetch
     * @throws NotFoundException
     */
    public function getCerts(User $user, ?int $id): array {
        $qb = $this->em->createQueryBuilder();
        $qb->select('c')->from('HoneySens\app\models\entities\SSLCert', 'c');
        if($user->role !== UserRole::ADMIN) {
            $qb->join(Sensor::class, 's', Join::WITH, 's.EAPOLCACert = c')
                ->join('s.division', 'd')
                ->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $user->getId());
        }
        try {
            if ($id !== null) {
                $qb->andWhere('c.id = :id')
                    ->setParameter('id', $id);
                return $qb->getQuery()->getSingleResult()->getState();
            } else {
                $certs = array();
                foreach ($qb->getQuery()->getResult() as $cert) {
                    $certs[] = $cert->getState();
                }
                return $certs;
            }
        } catch (NonUniqueResultException|NoResultException) {
            throw new NotFoundException();
        }
    }
}
