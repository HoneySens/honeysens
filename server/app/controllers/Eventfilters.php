<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\services\DivisionsService;
use HoneySens\app\services\EventFiltersService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Eventfilters extends RESTResource {

    static function registerRoutes($api) {
        $api->get('[/{id:\d+}]', [Eventfilters::class, 'get']);
        $api->post('', [Eventfilters::class, 'post']);
        $api->put('/{id:\d+}', [Eventfilters::class, 'put']);
        $api->delete('/{id:\d+}', [Eventfilters::class, 'delete']);
    }

    public function get(Request $request, Response $response, EventFiltersService $service, $id = null) {
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

    public function post(Request $request, Response $response, EventFiltersService $service, DivisionsService $divisionsService) {
        $this->assureAllowed('create');
        $filter = $service->create($request->getParsedBody(), $divisionsService, $this->getSessionUserID());
        $response->getBody()->write(json_encode($filter->getState()));
        return $response;
    }

    public function put(Request $request, Response $response, EventFiltersService $service, DivisionsService $divisionsService, $id) {
        $this->assureAllowed('update');
        $filter = $service->update($id, $request->getParsedBody(), $divisionsService, $this->getSessionUserID());
        $response->getBody()->write(json_encode($filter->getState()));
        return $response;
    }

    public function delete(Request $request, Response $response, EventFiltersService $service, DivisionsService $divisionsService, $id) {
        $this->assureAllowed('delete');
        $service->delete($id, $divisionsService, $this->getSessionUserID());
        $response->getBody()->write(json_encode([]));
        return $response;
    }
}
