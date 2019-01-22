<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\entities\EventFilter;
use HoneySens\app\models\entities\EventFilterCondition;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\models\exceptions\NotFoundException;
use Respect\Validation\Validator as V;

class Eventfilters extends RESTResource {

    static function registerRoutes($app, $em, $services, $config, $messages) {
        $app->get('/api/eventfilters(/:id)/', function($id = null) use ($app, $em, $services, $config, $messages) {
            $controller = new Eventfilters($em, $services, $config);
            $criteria = array();
            $criteria['userID'] = $controller->getSessionUserID();
            $criteria['id'] = $id;
            try {
                $result = $controller->get($criteria);
            } catch(\Exception $e) {
                throw new NotFoundException();
            }
            echo json_encode($result);
        });

        $app->post('/api/eventfilters', function() use ($app, $em, $services, $config, $messages) {
            $controller = new Eventfilters($em, $services, $config);
            $request = $app->request()->getBody();
            V::json()->check($request);
            $filterData = json_decode($request);
            $filter = $controller->create($filterData);
            echo json_encode($filter->getState());
        });

        $app->put('/api/eventfilters/:id', function($id) use ($app, $em, $services, $config, $messages) {
            $controller = new Eventfilters($em, $services, $config);
            $request = $app->request()->getBody();
            V::json()->check($request);
            $filterData = json_decode($request);
            $filter = $controller->update($id, $filterData);
            echo json_encode($filter->getState());
        });

        $app->delete('/api/eventfilters/:id', function($id) use ($app, $em, $services, $config, $messages) {
            $controller = new Eventfilters($em, $services, $config);
            $controller->delete($id);
            echo json_encode([]);
        });
    }

    /**
     * Creates and returns a new filter condition entity with the provided attributes.
     * - field: The field this condition applies to ("key")
     * - type: Further specification of the field type
     * - value: Value this condition uses for comparison
     *
     * @param stdClass $conditionData
     * @return EventFilterCondition
     * @throws BadRequestException
     */
    private function createCondition($conditionData) {
        // Validation
        V::objectType()
            ->attribute('field', V::intVal()->between(0, 3))
            ->attribute('type', V::intVal()->between(0, 3))
            ->attribute('value', V::stringType())
            ->check($conditionData);
        if($conditionData->field == EventFilterCondition::FIELD_CLASSIFICATION) {
            V::intVal()->between(0, 4)->check($conditionData->value);
        } elseif($conditionData->field == EventFilterCondition::FIELD_SOURCE) {
            if($conditionData->type == EventFilterCondition::TYPE_SOURCE_VALUE) {
                V::ip()->check($conditionData->value);
            } elseif($conditionData->type == EventFilterCondition::TYPE_SOURCE_IPRANGE) {
                V::regex('/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)-(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/')
                    ->check($conditionData->value);
            } elseif($conditionData->type == EventFilterCondition::TYPE_SOURCE_REGEX) {
                // Unused
                throw new BadRequestException();
            }
        } elseif($conditionData->field == EventFilterCondition::FIELD_TARGET) {
            V::intVal()->between(0, 65535)->check($conditionData->value);
        } elseif($conditionData->field == EventFilterCondition::FIELD_PROTOCOL) {
            V::intVal()->between(0, 2)->check($conditionData->value);
        }
        // Entity creation
        $condition = new EventFilterCondition();
        $condition->setField($conditionData->field)
            ->setType($conditionData->type)
            ->setValue($conditionData->value);
        return $condition;
    }

    /**
     * Updates an existing filter condition with the provided attributes.
     * - field: The field this condition applies to ("key")
     * - type: Further specification of the field type
     * - value: Value this condition uses for comparison
     *
     * @param EventFilterCondition $condition
     * @param stdClass $conditionData
     * @throws BadRequestException
     */
    private function updateCondition(EventFilterCondition $condition, $conditionData) {
        // Validation
        V::objectType()
            ->attribute('field', V::intVal()->between(0, 3))
            ->attribute('type', V::intVal()->between(0, 3))
            ->attribute('value', V::stringType())
            ->check($conditionData);
        if($conditionData->field == EventFilterCondition::FIELD_CLASSIFICATION) {
            V::intVal()->between(0, 4)->check($conditionData->value);
        } elseif($conditionData->field == EventFilterCondition::FIELD_SOURCE) {
            if($conditionData->type == EventFilterCondition::TYPE_SOURCE_VALUE) {
                V::ip()->check($conditionData->value);
            } elseif($conditionData->type == EventFilterCondition::TYPE_SOURCE_IPRANGE) {
                V::regex('/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)-(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/')
                    ->check($conditionData->value);
            } elseif($conditionData->type == EventFilterCondition::TYPE_SOURCE_REGEX) {
                // Unused
                throw new BadRequestException();
            }
        } elseif($conditionData->field == EventFilterCondition::FIELD_TARGET) {
            V::intVal()->between(0, 65535)->check($conditionData->value);
        } elseif($conditionData->field == EventFilterCondition::FIELD_PROTOCOL) {
            V::intVal()->between(0, 2)->check($conditionData->value);
        }
        // Entity creation
        $condition->setField($conditionData->field)
            ->setType($conditionData->type)
            ->setValue($conditionData->value);
    }

    /**
     * Fetches event filters from the DB by various criteria:
     * - userID: return only filters that this user is allowed to see (determined by his division membership)
     * - id: return the filter with the given id
     * If no criteria are given, all filters are returned
     *
     * @param array $criteria
     * @return array
     */
    public function get($criteria) {
        $this->assureAllowed('get');
        $qb = $this->getEntityManager()->createQueryBuilder();
        $qb->select('f')->from('HoneySens\app\models\entities\EventFilter', 'f');
        if(V::key('userID', V::intType())->validate($criteria)) {
            $qb->join('f.division', 'd')
                ->andWhere(':userid MEMBER OF d.users')
                ->setParameter('userid', $criteria['userID']);
        }
        if(V::key('id', V::intVal())->validate($criteria)) {
            $qb->andWhere('f.id = :id')
                ->setParameter('id', $criteria['id']);
            return $qb->getQuery()->getSingleResult()->getState();
        } else {
            $filters = array();
            foreach($qb->getQuery()->getResult() as $filter) {
                $filters[] = $filter->getState();
            }
            return $filters;
        }
    }

    /**
     * Creates and persists a new EventFilter object.
     * The following parameters are required:
     * - name: Name of this filter
     * - type: Type of this filter (currently only '0', whitelist, is supported)
     * - division: The Division id this filter belongs to
     * - conditions: Array specifying a list of filter conditions to add. Each item is another array
     *               specifying condition data.
     *
     * @param stdClass $data
     * @return EventFilter
     */
    public function create($data) {
        $this->assureAllowed('create');
        // Validation
        V::objectType()
            ->attribute('name', V::alnum('._-')->length(1, 255))
            ->attribute('type', V::intVal()->equals(0))
            ->attribute('division', V::intVal())
            ->attribute('conditions', V::arrayVal()->each(V::objectType()))
            ->check($data);
        // Persistence
        $filter = new EventFilter();
        $em = $this->getEntityManager();
        $division = $em->getRepository('HoneySens\app\models\entities\Division')->find($data->division);
        V::objectType()->check($division);
        $filter->setName($data->name)
            ->setType($data->type)
            ->setDivision($division);
        foreach($data->conditions as $conditionData) {
            $condition = $this->createCondition($conditionData);
            $filter->addCondition($condition);
            $em->persist($condition);
        }
        $em->persist($filter);
        $em->flush();
        return $filter;
    }

    /**
     * Updates an existing EventFilter object.
     * The following parameters are required:
     * - name: Name of this filter
     * - type: Type of this filter (currently only '0', whitelist, is supported)
     * - division: The Division id this filter belongs to
     * - conditions: Array specifying a list of filter conditions to add. Each item is another array
     *               specifying condition data.
     *
     * @param int $id
     * @param stdClass $data
     * @return EventFilter
     */
    public function update($id, $data) {
        $this->assureAllowed('update');
        // Validation
        V::intVal()->check($id);
        V::objectType()
            ->attribute('name', V::alnum('._-')->length(1, 255))
            ->attribute('type', V::intVal()->equals(0))
            ->attribute('division', V::intVal())
            ->attribute('conditions', V::arrayVal()->each(V::objectType()))
            ->check($data);
        // Persistence
        $em = $this->getEntityManager();
        $filter = $em->getRepository('HoneySens\app\models\entities\EventFilter')->find($id);
        V::objectType()->check($filter);
        $filter->setName($data->name);
        $filter->setType($data->type);
        $division = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\Division')->find($data->division);
        V::objectType()->check($division);
        $filter->setDivision($division);
        // Process condition association
        $conditionRepository = $em->getRepository('HoneySens\app\models\entities\EventFilterCondition');
        $forUpdate = array();
        $toAdd = array();
        foreach($data->conditions as $conditionData) {
            if(V::attribute('id')->validate($conditionData)) $forUpdate[] = $conditionData->id;
            else $toAdd[] = $conditionData;
        }
        $tasks = $this->updateCollection($filter->getConditions(), $forUpdate, $conditionRepository);
        foreach($tasks['update'] as $condition) {
            foreach($data->conditions as $conditionData) {
                if(V::attribute('id')->validate($conditionData) && $conditionData->id == $condition->getId())
                    $this->updateCondition($condition, $conditionData);
            }
        }
        foreach($tasks['remove'] as $condition) {
            $filter->removeCondition($condition);
            $em->remove($condition);
        }
        foreach($toAdd as $conditionData) {
            $condition = $this->createCondition($conditionData);
            $filter->addCondition($condition);
            $em->persist($condition);
        }
        $em->flush();
        return $filter;
    }

    public function delete($id) {
        $this->assureAllowed('delete');
        // Validation
        V::intVal()->check($id);
        // Persistence
        $em = $this->getEntityManager();
        $filter = $this->getEntityManager()->getRepository('HoneySens\app\models\entities\EventFilter')->find($id);
        V::objectType()->check($filter);
        $em->remove($filter);
        $em->flush();
    }
}