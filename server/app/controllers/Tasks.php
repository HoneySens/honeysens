<?php
namespace HoneySens\app\controllers;

use Exception;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\services\TasksService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Tasks extends RESTResource {

    static function registerRoutes($api) {
        $api->get('[/{id:\d+}]', [Tasks::class, 'get']);
        $api->get('/{id:\d+}/result[/{delete:\d+}]', [Tasks::class, 'downloadResult']);
        $api->get('/status', [Tasks::class, 'getStatus']);
        $api->post('/upload', [Tasks::class, 'upload']);
        $api->delete('/{id:\d+}', [Tasks::class, 'delete']);
    }

    public function get(Response $response, TasksService $service, $id = null) {
        $this->assureAllowed('get');
        $criteria = array(
            'userID' => $this->getSessionUserID(),
            'id' => $id);
        try {
            $result = $service->get($criteria);
        } catch(Exception $e) {
            throw new NotFoundException();
        }
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function downloadResult(TasksService $service, $id, $delete = 0) {
        $this->assureAllowed('get');
        [$filePath, $fileName, $callback] = $service->downloadResult($id, $this->getSessionUser(), boolval($delete));
        $this->offerFile($filePath, $fileName, $callback);
    }

    public function getStatus(Response $response, TasksService $service) {
        $this->assureAllowed('get');
        try {
            $result = array('queue_length' => $service->getBrokerQueueLength());
            $response->getBody()->write(json_encode($result));
            return $response;
        } catch(Exception $e) {
            throw new NotFoundException();
        }
    }

    /**
     * Generic endpoint to upload files, returns the ID of the associated verification task.
     */
    public function upload(Response $response, TasksService $service) {
        $this->assureAllowed('upload');
        $state = $service->upload($this->getSessionUser());
        $response->getBody()->write(json_encode($state));
        return $response;
    }

    public function delete(Response $response, TasksService $service, $id) {
        $this->assureAllowed('delete');
        $service->delete($id, $this->getSessionUser());
        $response->getBody()->write(json_encode([]));
        return $response;
    }
}
