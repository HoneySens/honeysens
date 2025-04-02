<?php
namespace HoneySens\app\controllers;

use HoneySens\app\services\TasksService;
use Psr\Http\Message\ResponseInterface as Response;
use Respect\Validation\Validator as V;

class Tasks extends RESTResource {

    static function registerRoutes($api): void {
        $api->get('[/{id:\d+}]', [Tasks::class, 'getTasks']);
        $api->get('/{id:\d+}/result[/{delete:\d+}]', [Tasks::class, 'downloadResult']);
        $api->get('/status', [Tasks::class, 'getStatus']);
        $api->post('/upload', [Tasks::class, 'uploadFile']);
        $api->delete('/{id:\d+}', [Tasks::class, 'deleteTask']);
    }

    public function getTasks(Response $response, TasksService $service, ?int $id = null): Response {
        $this->assureAllowed('get');
        $result = $service->getTasks($this->getSessionUser(), $id);
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
    public function uploadFile(Response $response, TasksService $service): Response {
        $this->assureAllowed('upload');
        $state = $service->uploadFile($this->getSessionUser());
        $response->getBody()->write(json_encode($state));
        return $response;
    }

    public function deleteTask(Response $response, TasksService $service, int $id): Response {
        $this->assureAllowed('delete');
        $service->deleteTask($this->getSessionUser(), $id);
        $response->getBody()->write(json_encode([]));
        return $response;
    }
}
