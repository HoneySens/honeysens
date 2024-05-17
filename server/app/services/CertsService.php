<?php
namespace HoneySens\app\services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join as Join;
use HoneySens\app\models\entities\Sensor;
use Respect\Validation\Validator as V;

class CertsService {

    private EntityManager $em;

    public function __construct(EntityManager $em) {
        $this->em= $em;
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
        $qb = $this->em->createQueryBuilder();
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
