<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\constants\EventFilterConditionField;
use HoneySens\app\models\constants\EventFilterConditionType;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\services\EventFiltersService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;

class Eventfilters extends RESTResource {

    static function registerRoutes($api): void {
        $api->get('[/{id:\d+}]', [Eventfilters::class, 'getEventFilters']);
        $api->post('', [Eventfilters::class, 'createEventFilter']);
        $api->put('/{id:\d+}', [Eventfilters::class, 'updateEventFilter']);
        $api->delete('/{id:\d+}', [Eventfilters::class, 'deleteEventFilter']);
    }

    public function getEventFilters(Response $response, EventFiltersService $service, ?int $id = null): Response {
        $this->assureAllowed('get');
        $result = $service->getEventFilters($this->getSessionUser(), $id);
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function createEventFilter(Request $request, Response $response, EventFiltersService $service): Response {
        $this->assureAllowed('create');
        $data = $request->getParsedBody();
        $this->assertValidFilter($data);
        $filter = $service->createEventFilter(
            $this->getSessionUser(),
            $data['name'],
            $data['division'],
            $data['conditions'],
            $data['description']
        );
        $response->getBody()->write(json_encode($filter->getState()));
        return $response;
    }

    public function updateEventFilter(Request $request, Response $response, EventFiltersService $service, int $id): Response {
        $this->assureAllowed('update');
        $data = $request->getParsedBody();
        $this->assertValidFilter($data, true);
        $filter = $service->updateEventFilter(
            $this->getSessionUser(),
            $id,
            $data['name'],
            $data['division'],
            $data['conditions'],
            $data['description'],
            $data['enabled']
        );
        $response->getBody()->write(json_encode($filter->getState()));
        return $response;
    }

    public function deleteEventFilter(Response $response, EventFiltersService $service, int $id): Response {
        $this->assureAllowed('delete');
        $service->deleteEventFilter($id, $this->getSessionUser());
        $response->getBody()->write(json_encode([]));
        return $response;
    }

    private function assertValidFilter(array $data, bool $isUpdate = false): void {
        V::arrayType()
            ->key('name', V::alnum('._- ')->length(1, 255))
            ->key('division', V::intVal())
            ->key('conditions', V::arrayType()->each(V::arrayType()))
            ->key('description', V::optional(V::stringType()->length(1, 65535)))
            ->check($data);
        if($isUpdate) V::key('enabled', V::boolType())->check($data);
        foreach($data['conditions'] as $condition) {
            V::key('field', V::intVal()->between(0, 3))
                ->key('type', V::intVal()->between(0, 3))
                ->key('value', V::stringType())
                ->check($condition);
            switch($condition['field']) {
                case EventFilterConditionField::CLASSIFICATION:
                    V::intVal()->between(0, 4)->check($condition['value']);
                    break;
                case EventFilterConditionField::SOURCE:
                    switch($condition['type']) {
                        case EventFilterConditionType::SOURCE_VALUE:
                            V::ip()->check($condition['value']);
                            break;
                        case EventFilterConditionType::SOURCE_IPRANGE:
                            V::regex('/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)-(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/')
                                ->check($condition['value']);
                            break;
                        case EventFilterConditionType::SOURCE_REGEX:
                            // Unused
                            throw new BadRequestException();
                    }
                    break;
                case EventFilterConditionField::TARGET:
                    V::intVal()->between(0, 65535)->check($condition['value']);
                    break;
                case EventFilterConditionField::PROTOCOL:
                    V::intVal()->between(0, 2)->check($condition['value']);
                    break;
            }
        }
    }
}
