<?php
namespace HoneySens\app\services;

use Doctrine\ORM\EntityManager;
use Respect\Validation\Validator as V;

class StatsService {

    private EntityManager $em;

    public function __construct(EntityManager $em) {
        $this->em= $em;
    }

    /**
     * Fetches statistical data from the DB by various criteria:
     * - userID: return only data that the given user is permitted to see
     * - year: returns only data that belongs to the given year
     * - month: return only data that belongs to the given month
     * - division: return only data that belongs to the given division
     *
     * @param array $criteria
     * @return array
     */
    public function get($criteria) {
        // Default to current year
        $year = date('Y');
        if (V::key('year', V::intVal()->between(1970, 2200))->validate($criteria)) {
            $year = $criteria['year'];
        }
        $month = null;
        if (V::key('month', V::intVal()->between(0, 12))->validate($criteria)) {
            $month = $criteria['month'];
        }
        $division = null;
        if (V::key('division', V::intVal())->validate($criteria)) {
            $division = $criteria['division'];
        }

        // Event timeline
        $timelineQB = $this->em->createQueryBuilder();
        if ($month) {
            // Month specified, show single days
            $timelineQB->select(array('COUNT(e) AS events', 'DAY(e.timestamp) AS tick'))
                ->from('HoneySens\app\models\entities\Event', 'e')
                ->join('e.sensor', 's')
                ->join('s.division', 'd')
                ->groupBy('tick')
                ->orderBy('tick', 'ASC')
                ->andWhere($timelineQB->expr()->eq('MONTH(e.timestamp)', ':month'))
                ->andWhere($timelineQB->expr()->eq('YEAR(e.timestamp)', ':year'))
                ->setParameter('month', $month)
                ->setParameter('year', $year);
        } else {
            // No month specified - show a whole year
            $timelineQB->select(array('COUNT(e) AS events', 'MONTH(e.timestamp) AS tick'))
                ->from('HoneySens\app\models\entities\Event', 'e')
                ->join('e.sensor', 's')
                ->join('s.division', 'd')
                ->groupBy('tick')
                ->orderBy('tick', 'ASC')
                ->andWhere($timelineQB->expr()->eq('YEAR(e.timestamp)', ':year'))
                ->setParameter('year', $year);

        }
        if (V::key('userID', V::intType())->validate($criteria)) {
            $timelineQB->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $criteria['userID']);
        }
        if ($division) {
            $timelineQB->andWhere($timelineQB->expr()->eq('d.id', ':division'))
                ->setParameter('division', $division);
        }
        $eventsTimeline = $timelineQB->getQuery()->getResult();

        // Classification breakdown
        $classificationQB = $this->em->createQueryBuilder();
        $classificationQB->select(array('COUNT(e) AS events', 'e.classification as classification'))
            ->from('HoneySens\app\models\entities\Event', 'e')
            ->join('e.sensor', 's')
            ->join('s.division', 'd')
            ->groupBy('classification')
            ->andWhere($timelineQB->expr()->eq('YEAR(e.timestamp)', ':year'))
            ->setParameter('year', $year);
        if ($month) {
            $classificationQB->andWhere($classificationQB->expr()->eq('MONTH(e.timestamp)', ':month'))
                ->setParameter('month', $month);
        }
        if (V::key('userID', V::intType())->validate($criteria)) {
            $classificationQB->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $criteria['userID']);
        }
        if ($division) {
            $classificationQB->andWhere($classificationQB->expr()->eq('d.id', ':division'))
                ->setParameter('division', $criteria['division']);
        }
        $classificationBreakdown = $classificationQB->getQuery()->getResult();
        return array(
            'year' => $year,
            'month' => $month,
            'division' => $division,
            'events_timeline' => $eventsTimeline,
            'classification' => $classificationBreakdown
        );
    }
}