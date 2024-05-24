<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\services\PlatformsService;
use HoneySens\app\services\TasksService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Platforms extends RESTResource {

    static function registerRoutes($api) {
        $api->get('[/{id:\d+}]', [Platforms::class, 'get']);
        $api->get('/firmware/{id:\d+}', [Platforms::class, 'getFirmware']);
        $api->post('/firmware', [Platforms::class, 'createFirmware']);
        $api->put('/{id:\d+}', [Platforms::class, 'updateFirmware']);
        $api->delete('/firmware/{id:\d+}', [Platforms::class, 'deleteFirmware']);
        $api->get('/firmware/{id:\d+}/raw', [Platforms::class, 'downloadFirmware']);
        $api->get('/{id:\d+}/firmware/current', [Platforms::class, 'downloadCurrentFirmware']);
    }

    public function get(Response $response, PlatformsService $service, $id = null) {
        $this->assureAllowed('get');
        $criteria = array('id' => $id);
        try {
            $result = $service->get($criteria);
        } catch(\Exception $e) {
            throw new NotFoundException();
        }
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function getFirmware(Response $response, PlatformsService $service, $id) {
        $this->assureAllowed('get');
        $firmware = $service->getFirmware($id);
        $response->getBody()->write(json_encode($firmware->getState()));
        return $response;
    }

    public function createFirmware(Request $request, Response $response, PlatformsService $service, TasksService $tasksService) {
        // Requires a successfully completed verification task
        $this->assureAllowed('create');
        $firmware = $service->createFirmware($request->getParsedBody(), $this->getSessionUser(), $tasksService);
        $response->getBody()->write(json_encode($firmware->getState()));
        return $response;
    }

    public function updateFirmware(Request $request, Response $response, PlatformsService $service, $id) {
        $this->assureAllowed('update');
        $platform = $service->updateFirmware($id, $request->getParsedBody());
        $response->getBody()->write(json_encode($platform->getState()));
        return $response;
    }

    public function deleteFirmware(Response $response, PlatformsService $service, $id) {
        $this->assureAllowed('delete');
        $service->deleteFirmware($id);
        $response->getBody()->write(json_encode([]));
        return $response;
    }

    public function downloadFirmware(PlatformsService $service, $id) {
        // Authenticate either as sensor or with a user session
        try {
            $sensor = $this->validateSensorRequest('get');
            $this->setMACHeaders($sensor, 'get');
        } catch (ForbiddenException $e) {
            $this->assureAllowed('download');
        }
        [$firmwarePath, $downloadName] = $service->getFirmwareDownload($id);
        $this->offerFile($firmwarePath, $downloadName);
    }

    public function downloadCurrentFirmware(PlatformsService $service, $id) {
        $this->assureAllowed('download');
        [$firmwarePath, $downloadName] = $service->downloadCurrentFirmwareForPlatform($id);
        $this->offerFile($firmwarePath, $downloadName);
    }
}
