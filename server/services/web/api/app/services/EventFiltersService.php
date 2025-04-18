<?php
namespace HoneySens\app\services;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use HoneySens\app\models\constants\EventFilterConditionField;
use HoneySens\app\models\constants\EventFilterConditionType;
use HoneySens\app\models\constants\LogResource;
use HoneySens\app\models\constants\UserRole;
use HoneySens\app\models\entities\EventFilter;
use HoneySens\app\models\entities\EventFilterCondition;
use HoneySens\app\models\entities\User;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\models\exceptions\SystemException;
use HoneySens\app\models\Utils;
use NoiseLabs\ToolKit\ConfigParser\ConfigParser;

class EventFiltersService extends Service {

    private ConfigParser $config;
    private LogService $logger;

    public function __construct(ConfigParser $config, EntityManager $em, LogService $logger) {
        parent::__construct($em);
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * Fetches event filters from the DB.
     *
     * @param User $user User for which to retrieve associated entities; admins receive all entities
     * @param int|null $id ID of a specific filter to fetch
     * @throws NotFoundException
     */
    public function getEventFilters(User $user, ?int $id = null): array {
        $qb = $this->em->createQueryBuilder();
        $qb->select('f')->from('HoneySens\app\models\entities\EventFilter', 'f');
        if($user->role !== UserRole::ADMIN) {
            $qb->join('f.division', 'd')
                ->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $user->getId());
        }
        try {
            if($id !== null) {
                $qb->andWhere('f.id = :id')
                    ->setParameter('id', $id);
                return $qb->getQuery()->getSingleResult()->getState();
            } else {
                $filters = array();
                foreach($qb->getQuery()->getResult() as $filter) {
                    $filters[] = $filter->getState();
                }
                return $filters;
            }
        } catch (NonUniqueResultException|NoResultException) {
            throw new NotFoundException();
        }
    }

    /**
     * Creates a new event filter.

     * @param User $user The user which performs this operation
     * @param string $name Name of this filter
     * @param int $divisionID The division this filter is created for
     * @param array $conditions Array specifying a list of filter conditions to add. Each item is another array specifying condition data.
     * @param string|null $description Free-form text that describes the filter's intention (can be null, depending on global settings)
     * @throws BadRequestException
     * @throws SystemException
     * @throws ForbiddenException
     */
    public function createEventFilter(User $user, string $name, int $divisionID, array $conditions, ?string $description): EventFilter {
        if($user->role !== UserRole::ADMIN)
            $this->assureUserAffiliation($divisionID, $user->getId());
        $division = $this->em->getRepository('HoneySens\app\models\entities\Division')->find($divisionID);
        if($division === null) throw new BadRequestException();
        $filter = new EventFilter();
        $filter->name = $name;
        $filter->description = $description;
        $division->addEventFilter($filter);
        try {
            foreach ($conditions as $conditionData) {
                $conditionField = EventFilterConditionField::from($conditionData['field']);
                $conditionType = EventFilterConditionType::from($conditionData['type']);
                $condition = $this->createCondition($conditionField, $conditionType, $conditionData['value']);
                $filter->addCondition($condition);
                $this->em->persist($condition);
            }
            $this->em->persist($filter);
            $this->em->flush();
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        $this->logger->log(sprintf('Event filter %s (ID %d) created with %d condition(s)', $filter->name, $filter->getId(), sizeof($filter->getConditions())), LogResource::EVENTFILTERS, $filter->getId());
        return $filter;
    }

    /**
     * Updates an existing event filter and its associated filter conditions.
     *
     * @param User $user Session user that calls this service
     * @param int $id Event  filter ID to update
     * @param string $name New name of this filter
     * @param int $divisionID New division this filter should be associated with
     * @param array $conditions Array specifying a list of filter conditions. Each item is another array specifying condition data.
     * @param string|null $description Free-form text that describes the filter's intention (can be null, depending on global settings)
     * @param bool $enabled Whether this filter should be active and taken into consideration when evaluating incoming events
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws SystemException
     */
    public function updateEventFilter(User $user, int $id, string $name, int $divisionID, array $conditions, ?string $description, bool $enabled): EventFilter {
        try {
            $filter = $this->em->getRepository('HoneySens\app\models\entities\EventFilter')->find($id);
        } catch (ORMException $e) {
            throw new SystemException($e);
        }
        if($filter === null) throw new BadRequestException();
        $userIsAdmin = $user->role === UserRole::ADMIN;
        if(!$userIsAdmin)
            $this->assureUserAffiliation($filter->division->getId(), $user->getId());
        if($filter->division->getId() !== $divisionID && !$userIsAdmin)
            // If division association changes, assert the user is associated with the new division
            $this->assureUserAffiliation($divisionID, $user->getId());
        if($this->config->getBoolean('misc', 'require_filter_description')
            && ($description === null || strlen($description) == 0)) {
            // If the description requirement isn't met, only update the 'enabled' flag
            $filter->enabled = $enabled;
            try {
                $this->em->flush();
            } catch (ORMException $e) {
                throw new SystemException($e);
            }
            return $filter;
        }
        $filter->name = $name;
        $filter->description = $description;
        $filter->enabled = $enabled;
        try {
            $division = $this->em->getRepository('HoneySens\app\models\entities\Division')->find($divisionID);
            $conditionRepository = $this->em->getRepository('HoneySens\app\models\entities\EventFilterCondition');
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        if($division === null) throw new BadRequestException();
        $division->addEventFilter($filter);
        // Process condition association
        $forUpdate = array();
        $toAdd = array();
        foreach($conditions as $conditionData) {
            if(array_key_exists('id', $conditionData)) $forUpdate[] = $conditionData['id'];
            else $toAdd[] = $conditionData;
        }
        $tasks = Utils::updateCollection($filter->getConditions(), $forUpdate, $conditionRepository);
        foreach($tasks['update'] as $condition) {
            foreach($conditions as $conditionData) {
                if(array_key_exists('id', $conditionData) && $conditionData['id'] === $condition->getId())
                    $condition->field = EventFilterConditionField::from($conditionData['field']);
                    $condition->type = EventFilterConditionType::from($conditionData['type']);
                    $condition->value = $conditionData['value'];
            }
        }
        try {
            foreach ($tasks['remove'] as $condition) {
                $filter->removeCondition($condition);
                $this->em->remove($condition);
            }
            foreach ($toAdd as $conditionData) {
                $conditionField = EventFilterConditionField::from($conditionData['field']);
                $conditionType = EventFilterConditionType::from($conditionData['type']);
                $condition = $this->createCondition($conditionField, $conditionType, $conditionData['value']);
                $filter->addCondition($condition);
                $this->em->persist($condition);
            }
            $this->em->flush();
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        $this->logger->log(sprintf('Event filter %s (ID %d) updated with %d conditions', $filter->name, $filter->getId(), sizeof($filter->getConditions())), LogResource::EVENTFILTERS, $filter->getId());
        return $filter;
    }

    /**
     * Removes an event filter and its associated filter conditions.
     *
     * @param int $id Event filter ID to delete
     * @param User $user Session user that calls this services
     * @throws BadRequestException
     * @throws ForbiddenException
     * @throws SystemException
     */
    public function deleteEventFilter(int $id, User $user): void {
        $filter = $this->em->getRepository('HoneySens\app\models\entities\EventFilter')->find($id);
        if($filter === null) throw new BadRequestException();
        $userIsAdmin = $user->role === UserRole::ADMIN;
        if(!$userIsAdmin)
            $this->assureUserAffiliation($filter->division->getId(), $user->getId());
        $filter->division->removeEventFilter($filter);
        $fid = $filter->getId();
        try {
            $this->em->remove($filter);
            $this->em->flush();
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
        $this->logger->log(sprintf('Event filter %s (ID %d) deleted', $filter->name, $fid), LogResource::EVENTFILTERS, $fid);
    }

    /**
     * Creates and returns a new filter condition entity with the given attributes.
     *
     * @param EventFilterConditionField $field The field this condition applies to ("key"))
     * @param EventFilterConditionType $type The type of check to perform, essentially a type hint for $value
     * @param mixed $value The value to check the $field against
     */
    private function createCondition(EventFilterConditionField $field, EventFilterConditionType $type, mixed $value): EventFilterCondition {
        $condition = new EventFilterCondition();
        $condition->field = $field;
        $condition->type = $type;
        $condition->value = $value;
        return $condition;
    }
}
