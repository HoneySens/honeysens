<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\constants\EventFilterConditionField;
use HoneySens\app\models\constants\EventFilterConditionType;
use HoneySens\app\models\exceptions\BadRequestException;
use HoneySens\app\services\EventFiltersService;
use NoiseLabs\ToolKit\ConfigParser\ConfigParser;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;

class Eventfilters extends RESTResource {

    static function registerRoutes($api) {
        $api->get('[/{id:\d+}]', [Eventfilters::class, 'get']);
        $api->post('', [Eventfilters::class, 'post']);
        $api->put('/{id:\d+}', [Eventfilters::class, 'put']);
        $api->delete('/{id:\d+}', [Eventfilters::class, 'delete']);
    }

    public function get(Response $response, EventFiltersService $service, $id = null): Response {
        $this->assureAllowed('get');
        $result = $service->get($this->getSessionUser(), $id);
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function post(Request $request, Response $response, ConfigParser $config, EventFiltersService $service): Response {
        $this->assureAllowed('create');
        $data = $request->getParsedBody();
        $this->assertValidFilter($data, $config);
        $filter = $service->create(
            $this->getSessionUser(),
            $data['name'],
            $data['type'],
            $data['division'],
            $data['conditions'],
            $data['description']
        );
        $response->getBody()->write(json_encode($filter->getState()));
        return $response;
    }

    public function put(Request $request, Response $response, ConfigParser $config, EventFiltersService $service, int $id): Response {
        $this->assureAllowed('update');
        $data = $request->getParsedBody();
        $this->assertValidFilter($data, $config, true);
        $filter = $service->update(
            $this->getSessionUser(),
            $id,
            $data['name'],
            $data['type'],
            $data['division'],
            $data['conditions'],
            $data['description'],
            $data['enabled']
        );
        $response->getBody()->write(json_encode($filter->getState()));
        return $response;
    }

    public function delete(Response $response, EventFiltersService $service, int $id): Response {
        $this->assureAllowed('delete');
        $service->delete($id, $this->getSessionUser());
        $response->getBody()->write(json_encode([]));
        return $response;
    }

    private function assertValidFilter(array $data, ConfigParser $config, bool $isUpdate = false): void {
        V::arrayType()
            ->key('name', V::alnum('._-')->length(1, 255))
            ->key('type', V::intVal()->equals(0))
            ->key('division', V::intVal())
            ->key('conditions', V::arrayType()->each(V::arrayType()))
            ->check($data);
        if($isUpdate) V::key('enabled', V::boolType())->check($data);
        if($config->getBoolean('misc', 'require_filter_description'))
            V::key('description', V::stringType()->length(1, 65535))->check($data);
        else V::key('description', V::optional(V::stringType()->length(1, 65535)))->check($data);
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
