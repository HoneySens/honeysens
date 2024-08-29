<?php
namespace HoneySens\app\services;

use HoneySens\app\models\entities\User;

class StatsService extends Service {

    /**
     * Returns an event timeline and classification type breakdown,
     * filterable by month year and division.
     *
     * @param User $user User for which to retrieve stats; admins receive all data
     * @param int|null $divisionID Division for which to retrieve stats
     * @param int|null $year Year for which to retrieve stats, uses current year if null
     * @param int|null $month If not null, breaks down the results for a single month
     */
    public function get(User $user, ?int $divisionID = null, ?int $year = null, ?int $month = null): array {
        // Default to current year
        $year = $year ?? date('Y');

        // Event timeline
        $timelineQB = $this->em->createQueryBuilder();
        if ($month !== null) {
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
        if($user->getRole() !== User::ROLE_ADMIN) {
            $timelineQB->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $user->getId());
        }
        if($divisionID !== null) {
            $timelineQB->andWhere($timelineQB->expr()->eq('d.id', ':division'))
                ->setParameter('division', $divisionID);
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
        if ($month !== null) {
            $classificationQB->andWhere($classificationQB->expr()->eq('MONTH(e.timestamp)', ':month'))
                ->setParameter('month', $month);
        }
        if($user->getRole() !== User::ROLE_ADMIN) {
            $classificationQB->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $user->getId());
        }
        if ($divisionID !== null) {
            $classificationQB->andWhere($classificationQB->expr()->eq('d.id', ':division'))
                ->setParameter('division', $divisionID);
        }
        $classificationBreakdown = $classificationQB->getQuery()->getResult();
        return array(
            'year' => $year,
            'month' => $month,
            'division' => $divisionID,
            'events_timeline' => $eventsTimeline,
            'classification' => $classificationBreakdown
        );
    }
}