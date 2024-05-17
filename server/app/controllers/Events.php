<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\services\EventsService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;

class Events extends RESTResource {

    static function registerRoutes($api) {
        $api->get('[/{id:\d+}]', [Events::class, 'get']);
        $api->post('', [Events::class, 'post']);
        $api->put('[/{id:\d+}]', [Events::class, 'put']);
        $api->delete('', [Events::class, 'delete']);
    }

    public function get(Request $request, Response $response, EventsService $service, $id = null) {
        $this->assureAllowed('get');
        $criteria = $request->getQueryParams();
        $criteria['userID'] = $this->getSessionUserID();
        $criteria['id'] = $id;
        try {
            $result = $service->get($criteria, $this->getSessionUser());
        } catch(\Exception $e) {
            throw new NotFoundException();
        }
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function post(Request $request, Response $response, EventsService $service) {
        $requestBody = $request->getBody()->getContents();
        $sensor = $this->validateSensorRequest('create', $requestBody);
        // Parse sensor request as JSON even if no correct Content-Type header is set
        $service->create($sensor, json_decode($requestBody, true));
        $this->setMACHeaders($sensor, 'create');
        return $response;
    }

    public function put(Request $request, Response $response, EventsService $service, $id = null) {
        $this->assureAllowed('update');
        $eventData = $request->getParsedBody();
        $eventData['id'] = $id;
        $eventData['userID'] = $this->getSessionUserID();
        $service->update($eventData);
        $response->getBody()->write(json_encode([]));
        return $response;
    }

    public function delete(Request $request, Response $response, EventsService $service) {
        $criteria = $request->getParsedBody();
        // In case the current user can't delete events, force archiving
        try {
            $this->assureAllowed('delete');
            $archive = V::key('archive', V::boolType())->validate($criteria) && $criteria['archive'];
        } catch (\Exception $e) {
            $this->assureAllowed('archive');
            $archive = true;
        }
        $criteria['userID'] = $this->getSessionUserID();
        $service->delete($criteria, $archive);
        $response->getBody()->write(json_encode([]));
        return $response;
    }
}