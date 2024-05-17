<?php
namespace HoneySens\app\services;

use Doctrine\ORM\EntityManager;
use Respect\Validation\Validator as V;

class ContactsService {

    private EntityManager $em;

    public function __construct(EntityManager $em) {
        $this->em= $em;
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
        $qb = $this->em->createQueryBuilder();
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
