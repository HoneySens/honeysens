<?php
namespace HoneySens\app\controllers;

use HoneySens\app\services\TasksService;
use Psr\Http\Message\ResponseInterface as Response;
use Respect\Validation\Validator as V;

class Tasks extends RESTResource {

    static function registerRoutes($api) {
        $api->get('[/{id:\d+}]', [Tasks::class, 'get']);
        $api->get('/{id:\d+}/result[/{delete:\d+}]', [Tasks::class, 'downloadResult']);
        $api->get('/status', [Tasks::class, 'getStatus']);
        $api->post('/upload', [Tasks::class, 'upload']);
        $api->delete('/{id:\d+}', [Tasks::class, 'delete']);
    }

    public function get(Response $response, TasksService $service, ?int $id = null): Response {
        $this->assureAllowed('get');
        $result = $service->get($this->getSessionUser(), $id);
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function downloadResult(TasksService $service, int $id, $delete = 0): Response {
        $this->assureAllowed('get');
        V::intVal()->between(0, 1)->check($delete);
        [$filePath, $fileName, $callback] = $service->downloadResult($this->getSessionUser(), $id, boolval($delete));
        $this->offerFile($filePath, $fileName, $callback);
    }

    public function getStatus(Response $response, TasksService $service): Response {
        $this->assureAllowed('get');
        $result = array('queue_length' => $service->getBrokerQueueLength());
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    /**
     * Generic endpoint to upload files, returns the associated verification task's state.
     */
    public function upload(Response $response, TasksService $service): Response {
        $this->assureAllowed('upload');
        $state = $service->upload($this->getSessionUser());
        $response->getBody()->write(json_encode($state));
        return $response;
    }

    public function delete(Response $response, TasksService $service, int $id): Response {
        $this->assureAllowed('delete');
        $service->delete($this->getSessionUser(), $id);
        $response->getBody()->write(json_encode([]));
        return $response;
    }
}
