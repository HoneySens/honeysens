<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\services\DivisionsService;
use HoneySens\app\services\EventsService;
use HoneySens\app\services\SensorsService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;

class Sensors extends RESTResource {

    static function registerRoutes($api) {
        $api->get('[/{id:\d+}]', [Sensors::class, 'get']);
        $api->post('', [Sensors::class, 'create']);
        $api->get('/status/by-sensor/{id:\d+}', [Sensors::class, 'getStatus']);
        $api->get('/config/{id:\d+}', [Sensors::class, 'requestConfigDownload']);
        $api->get('/firmware', [Sensors::class, 'getFirmware']);
        $api->post('/status', [Sensors::class, 'recordSensorStatus']);
        $api->put('/{id:\d+}', [Sensors::class, 'put']);
        $api->delete('/{id:\d+}', [Sensors::class, 'delete']);
    }

    public function get(Request $request, Response $response, SensorsService $service, $id = null) {
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

    public function create(Request $request, Response $response, SensorsService $service, DivisionsService $divisionsService) {
        $this->assureAllowed('create');
        $sensor = $service->create($request->getParsedBody(), $divisionsService, $this->getSessionUserID());
        $result = $service->getSensorState($sensor);
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function put(Request $request, Response $response, SensorsService $service, DivisionsService $divisionsService, $id) {
        $this->assureAllowed('update');
        $sensor = $service->update($id, $request->getParsedBody(), $divisionsService, $this->getSessionUserID());
        $result = $service->getSensorState($sensor);
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function delete(Request $request, Response $response, SensorsService $service, $id, DivisionsService $divisionsService, EventsService $eventsService) {
        $this->assureAllowed('delete');
        $criteria = $request->getParsedBody();
        // Validation
        try {
            // In case the current user can't delete events, force archiving
            $this->assureAllowed('delete', 'events');
            $archive = V::key('archive', V::boolType())->validate($criteria) && $criteria['archive'];
        } catch(\Exception $e) {
            $this->assureAllowed('archive', 'events');
            $archive = true;
        }
        $service->delete($id, $archive, $this->getSessionUserID(), $divisionsService, $eventsService);
        $response->getBody()->write(json_encode([]));
        return $response;
    }

    public function getStatus(Request $request, Response $response, SensorsService $service, $id) {
        $this->assureAllowed('get');
        $criteria = array(
            'userID' => $this->getSessionUserID(),
            'sensorID' => $id);
        $result = $service->getStatus($criteria);
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function requestConfigDownload(Request $request, Response $response, SensorsService $service, DivisionsService $divisionsService, $id) {
        $this->assureAllowed('downloadConfig');
        $task = $service->requestConfigDownload($id, $divisionsService, $this->getSessionUser(), $this->getSessionUserID());
        $response->getBody()->write(json_encode($task->getState()));
        return $response;
    }

    /**
     * This resource is used by authenticated sensors to receive firmware download details.
     * The return value is an array with platform names as keys and their respective access URIs as value.
     */
    public function getFirmware(Request $request, Response $response, SensorsService $service) {
        $sensor = $this->validateSensorRequest('get', '');
        $body = json_encode($service->getFirmwareURIs($sensor));
        $this->setMACHeaders($sensor, 'get', $body);
        $response->getBody()->write($body);
        return $response;
    }

    /**
     * Polling endpoint for sensors to send status data and receive their current configuration.
     */
    public function recordSensorStatus(Request $request, Response $response, SensorsService $service) {
        $requestBody = $request->getBody()->getContents();
        $sensor = $this->validateSensorRequest('create', $requestBody);
        // Parse sensor request as JSON even if no correct Content-Type header is set
        $sensorData = $service->poll($sensor, json_decode($requestBody, true), $this->getServerCert());
        $body = json_encode($sensorData);
        $this->setMACHeaders($sensor, 'create', $body);
        $response->getBody()->write($body);
        return $response;
    }
}
