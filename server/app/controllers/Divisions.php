<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\services\DivisionsService;
use HoneySens\app\services\EventsService;
use HoneySens\app\services\SensorsService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Divisions extends RESTResource {

    const ERROR_DUPLICATE = 1;

    static function registerRoutes($api) {
        $api->get('[/{id:\d+}]', [Divisions::class, 'get']);
        $api->post('', [Divisions::class, 'post']);
        $api->put('/{id:\d+}', [Divisions::class, 'put']);
        $api->delete('/{id:\d+}', [Divisions::class, 'delete']);
    }

    public function get(Response $response, DivisionsService $service, $id = null) {
        $this->assureAllowed('get');
        $criteria = array(
            'userID' => $this->getSessionUserID(),
            'id' => $id);
        try {
            $result = $service->get($criteria);
        } catch(\Exception $e) {
            throw new NotFoundException();
        }
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function post(Request $request, Response $response, DivisionsService $service) {
        $this->assureAllowed('create');
        $division = $service->create($request->getParsedBody());
        $response->getBody()->write(json_encode($division->getState()));
        return $response;
    }

    public function put(Request $request, Response $response, DivisionsService $service, $id) {
        $this->assureAllowed('update');
        $division = $service->update($id, $request->getParsedBody());
        $response->getBody()->write(json_encode($division->getState()));
        return $response;
    }

    public function delete(Request $request, Response $response, DivisionsService $service, EventsService $eventsService, SensorsService $sensorsService, $id) {
        $this->assureAllowed('delete');
        $service->delete($id, $request->getParsedBody(), $this->getSessionUserID(), $eventsService, $sensorsService);
        $response->getBody()->write(json_encode([]));
        return $response;
    }
}
