<?php
namespace HoneySens\app\services;

use HoneySens\app\models\constants\EventStatus;
use HoneySens\app\models\constants\UserRole;
use HoneySens\app\models\entities\User;

class StatsService extends Service {

    /**
     * Returns an event timeline, a classification type breakdown,
     * which are both filterable by month year and division. In addition,
     * returns total event counts for the current user (unfiltered).
     *
     * @param User $user User for which to retrieve stats; admins receive all data
     * @param int|null $divisionID Division for which to retrieve timeline/classification stats
     * @param int|null $year Year for which to retrieve timeline/classification stats, uses current year if null
     * @param int|null $month If not null, breaks down the timeline/classification results for a single month
     */
    public function getStats(User $user, ?int $divisionID = null, ?int $year = null, ?int $month = null): array {
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
        if($user->role !== UserRole::ADMIN) {
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
        if($user->role !== UserRole::ADMIN) {
            $classificationQB->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $user->getId());
        }
        if ($divisionID !== null) {
            $classificationQB->andWhere($classificationQB->expr()->eq('d.id', ':division'))
                ->setParameter('division', $divisionID);
        }
        $classificationBreakdown = $classificationQB->getQuery()->getResult();

        // Event stats
        $eventsByStatusQB = $this->em->createQueryBuilder()
            ->select(array('e.status', 'count(e.id) AS count'))
            ->from('HoneySens\app\models\entities\Event', 'e')
            ->join('e.sensor', 's')
            ->join('s.division', 'd')
            ->orderBy('e.status', 'asc')
            ->groupBy('e.status');
        if($user->role !== UserRole::ADMIN) {
            $eventsByStatusQB->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $user->getId());
        }
        $eventsByStatus = array(
            EventStatus::UNEDITED->value => 0,
            EventStatus::BUSY->value => 0,
            EventStatus::RESOLVED->value => 0,
            EventStatus::IGNORED->value => 0
        );
        foreach ($eventsByStatusQB->getQuery()->getResult() as $r) {
            $eventsByStatus[$r['status']->value] = $r['count'];
        }
        $uneditedEvents = $eventsByStatus[EventStatus::UNEDITED->value];
        $busyEvents = $eventsByStatus[EventStatus::BUSY->value];
        $resolvedEvents = $eventsByStatus[EventStatus::RESOLVED->value];
        $ignoredEvents = $eventsByStatus[EventStatus::IGNORED->value];
        $liveEvents = $uneditedEvents + $busyEvents + $resolvedEvents + $ignoredEvents;
        $archivedEventsQB = $this->em->createQueryBuilder()
            ->select('count(e.id)')
            ->from('HoneySens\app\models\entities\ArchivedEvent', 'e');
        if($user->role !== UserRole::ADMIN) {
            $archivedEventsQB
                ->join('e.division', 'd')
                ->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $user->getId());
        }
        $archivedEvents = $archivedEventsQB->getQuery()->getSingleScalarResult();

        return array(
            'events_total' => $liveEvents + $archivedEvents,
            'events_live' => $liveEvents,
            'events_unedited' => $uneditedEvents,
            'events_busy' => $busyEvents,
            'events_resolved' => $resolvedEvents,
            'events_ignored' => $ignoredEvents,
            'events_archived' => $archivedEvents,
            'year' => $year,
            'month' => $month,
            'division' => $divisionID,
            'events_timeline' => $eventsTimeline,
            'classification' => $classificationBreakdown
        );
    }
}
