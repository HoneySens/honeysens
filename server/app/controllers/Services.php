<?php
namespace HoneySens\app\controllers;

use HoneySens\app\services\SensorServicesService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;

class Services extends RESTResource {

    static function registerRoutes($api): void {
        $api->get('[/{id:\d+}]', [Services::class, 'getServices']);
        $api->post('', [Services::class, 'createService']);
        $api->put('/{id:\d+}', [Services::class, 'updateService']);
        $api->delete('/{id:\d+}', [Services::class, 'deleteService']);
        $api->delete('/revisions/{id:\d+}', [Services::class, 'deleteRevision']);
        $api->get('/status', [Services::class, 'getStatusSummary']);
        $api->get('/{id:\d+}/status', [Services::class, 'getStatus']);
    }

    public function getServices(Response $response, SensorServicesService $service, ?int $id = null): Response {
        $this->assureAllowed('get');
        $result = $service->getServices($id);
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    /**
     * Requires a reference to a successfully completed verification task.
     */
    public function createService(Request $request, Response $response, SensorServicesService $service): Response {
        $this->assureAllowed('create');
        $data = $request->getParsedBody();
        V::arrayType()->key('task', V::intVal())->check($data);
        $task = $service->createService($this->getSessionUser(), $data['task']);
        $response->getBody()->write(json_encode($task->getState()));
        return $response;
    }

    public function updateService(Request $request, Response $response, SensorServicesService $service, int $id): Response {
        $this->assureAllowed('update');
        $data = $request->getParsedBody();
        V::arrayType()->key('default_revision', V::stringType())->check($data);
        $sensorService = $service->updateService($id, $data['default_revision']);
        $response->getBody()->write(json_encode($sensorService->getState()));
        return $response;
    }

    public function deleteService(Response $response, SensorServicesService $service, int $id): Response {
        $this->assureAllowed('delete');
        $service->deleteService($id);
        $response->getBody()->write(json_encode([]));
        return $response;
    }

    public function deleteRevision(Response $response, SensorServicesService $service, int $id): Response {
        $this->assureAllowed('delete');
        $service->deleteRevision($id);
        $response->getBody()->write(json_encode([]));
        return $response;
    }

    public function getStatusSummary(Response $response, SensorServicesService $service): Response {
        $this->assureAllowed('get');
        $response->getBody()->write(json_encode($service->getStatusSummary()));
        return $response;
    }

    public function getStatus(Response $response, SensorServicesService $service, int $id): Response {
        $this->assureAllowed('get');
        $result = $service->getServiceStatus($id);
        $response->getBody()->write(json_encode($result));
        return $response;
    }
}
