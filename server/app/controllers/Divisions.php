<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\Utils;
use HoneySens\app\services\DivisionsService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;
use Slim\Interfaces\RouteCollectorProxyInterface;

class Divisions extends RESTResource {

    static function registerRoutes(RouteCollectorProxyInterface $api): void {
        $api->get('[/{id:\d+}]', [Divisions::class, 'getDivisions']);
        $api->post('', [Divisions::class, 'createDivision']);
        $api->put('/{id:\d+}', [Divisions::class, 'updateDivision']);
        $api->delete('/{id:\d+}', [Divisions::class, 'deleteDivision']);
    }

    public function getDivisions(Response $response, DivisionsService $service, ?int $id = null): Response {
        $this->assureAllowed('get');
        $result = $service->getDivisions($this->getSessionUser(), $id);
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function createDivision(Request $request, Response $response, DivisionsService $service): Response {
        $this->assureAllowed('create');
        $data = $request->getParsedBody();
        $this->assertValidDivision($data);
        $division = $service->createDivision($data['name'], $data['users'], $data['contacts']);
        $response->getBody()->write(json_encode($division->getState()));
        return $response;
    }

    public function updateDivision(Request $request, Response $response, DivisionsService $service, int $id): Response {
        $this->assureAllowed('update');
        $data = $request->getParsedBody();
        $this->assertValidDivision($data, true);
        $division = $service->updateDivision($id, $data['name'], $data['users'], $data['contacts']);
        $response->getBody()->write(json_encode($division->getState()));
        return $response;
    }

    public function deleteDivision(Request $request, Response $response, DivisionsService $service, int $id): Response {
        $this->assureAllowed('delete');
        $data = $request->getParsedBody();
        $archive = V::key('archive', V::boolType())->validate($data) && $data['archive'];
        $service->deleteDivision($id, $archive, $this->getSessionUser());
        $response->getBody()->write(json_encode([]));
        return $response;
    }

    private function assertValidDivision(array $data, bool $isUpdate = false): void {
        V::arrayType()
            ->key('name', V::alnum()->length(1, 255))
            ->key('users', V::arrayVal()->each(V::intType()))
            ->key('contacts', V::arrayVal()->each(V::arrayType()))
            ->check($data);
        foreach($data['contacts'] as $contact) {
            if($isUpdate) V::key('id', V::intType(), false)->check($contact);
            V::key('type', V::intType()->between(0, 1))
                ->key('sendWeeklySummary', V::boolVal())
                ->key('sendCriticalEvents', V::boolVal())
                ->key('sendAllEvents', V::boolVal())
                ->key('sendSensorTimeouts', V::boolVal())
                ->check($contact);
            switch($contact['type']) {
                case 0:
                    V::key('email', Utils::emailValidator())->check($contact);
                    break;
                case 1:
                    V::key('user', V::intVal())->check($contact);
                    break;
            }
        }
    }
}
