<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\services\SensorServicesService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Services extends RESTResource {

    static function registerRoutes($api) {
        $api->get('[/{id:\d+}]', [Services::class, 'get']);
        $api->post('', [Services::class, 'post']);
        $api->put('/{id:\d+}', [Services::class, 'put']);
        $api->delete('/{id:\d+}', [Services::class, 'delete']);
        $api->delete('/revisions/{id:\d+}', [Services::class, 'deleteRevision']);
        $api->get('/status', [Services::class, 'getStatusSummary']);
        $api->get('/{id:\d+}/status', [Services::class, 'getStatus']);
    }

    public function get(Request $request, Response $response, SensorServicesService $service, $id = null) {
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

    /**
     * Requires a reference to a successfully completed verification task.
     */
    public function post(Request $request, Response $response, SensorServicesService $service) {
        $this->assureAllowed('create');
        $result = $service->create($request->getParsedBody(), $this->getSessionUser());
        $response->getBody()->write(json_encode($result->getState()));
        return $response;
    }

    public function put(Request $request, Response $response, SensorServicesService $service, $id) {
        $this->assureAllowed('update');
        $result = $service->update($id, $request->getParsedBody());
        $response->getBody()->write(json_encode($result->getState()));
        return $response;
    }

    public function delete(Request $request, Response $response, SensorServicesService $service, $id) {
        $this->assureAllowed('delete');
        $service->delete($id);
        $response->getBody()->write(json_encode([]));
        return $response;
    }

    public function deleteRevision(Request $request, Response $response, SensorServicesService $service, $id) {
        $this->assureAllowed('delete');
        $service->deleteRevision($id);
        $response->getBody()->write(json_encode([]));
        return $response;
    }

    public function getStatusSummary(Request $request, Response $response, SensorServicesService $service) {
        $this->assureAllowed('get');
        $response->getBody()->write(json_encode($service->getStatusSummary()));
        return $response;
    }

    public function getStatus(Request $request, Response $response, SensorServicesService $service, $id) {
        $this->assureAllowed('get');
        $result = $service->getStatus($id);
        $response->getBody()->write(json_encode($result));
        return $response;
    }
}
