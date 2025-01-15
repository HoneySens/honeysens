<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\exceptions\ForbiddenException;
use HoneySens\app\services\PlatformsService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;

class Platforms extends RESTResource {

    static function registerRoutes($api): void {
        $api->get('[/{id:\d+}]', [Platforms::class, 'getPlatforms']);
        $api->get('/firmware/{id:\d+}', [Platforms::class, 'getFirmware']);
        $api->post('/firmware', [Platforms::class, 'createFirmware']);
        $api->put('/{id:\d+}', [Platforms::class, 'updateFirmware']);
        $api->delete('/firmware/{id:\d+}', [Platforms::class, 'deleteFirmware']);
        $api->get('/firmware/{id:\d+}/raw', [Platforms::class, 'downloadFirmware']);
        $api->get('/{id:\d+}/firmware/current', [Platforms::class, 'downloadCurrentFirmware']);
    }

    public function getPlatforms(Response $response, PlatformsService $service, ?int $id = null): Response {
        $this->assureAllowed('get');
        $result = $service->getPlatforms($id);
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function getFirmware(Response $response, PlatformsService $service, int $id): Response {
        $this->assureAllowed('get');
        $firmware = $service->getFirmware($id);
        $response->getBody()->write(json_encode($firmware->getState()));
        return $response;
    }

    public function createFirmware(Request $request, Response $response, PlatformsService $service): Response {
        // Requires a successfully completed upload verification task
        $this->assureAllowed('create');
        $data = $request->getParsedBody();
        V::arrayType()->key('task', V::intType())->check($data);
        $firmware = $service->createFirmware($this->getSessionUser(), $data['task']);
        $response->getBody()->write(json_encode($firmware->getState()));
        return $response;
    }

    public function updateFirmware(Request $request, Response $response, PlatformsService $service, int $id): Response {
        $this->assureAllowed('update');
        $data = $request->getParsedBody();
        V::arrayType()->key('default_firmware_revision', V::intVal())->check($data);
        $platform = $service->updateFirmware($id, $data['default_firmware_revision']);
        $response->getBody()->write(json_encode($platform->getState()));
        return $response;
    }

    public function deleteFirmware(Response $response, PlatformsService $service, int $id): Response {
        $this->assureAllowed('delete');
        $service->deleteFirmware($id);
        $response->getBody()->write(json_encode([]));
        return $response;
    }

    public function downloadFirmware(PlatformsService $service, int $id): void {
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

    public function downloadCurrentFirmware(PlatformsService $service, int $id): void {
        $this->assureAllowed('download');
        [$firmwarePath, $downloadName] = $service->downloadCurrentFirmwareForPlatform($id);
        $this->offerFile($firmwarePath, $downloadName);
    }
}
