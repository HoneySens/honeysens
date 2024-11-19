<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\constants\LogResource;
use HoneySens\app\services\LogService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;

class Logs extends RESTResource {

    static function registerRoutes($api): void {
        $api->get('/', [Logs::class, 'getLogs']);
    }

    public function getLogs(Request $request, Response $response, LogService $service): Response {
        $this->assureAllowed('get');
        $optionalParams = array();
        $queryParams = $request->getQueryParams();
        if(array_key_exists('user_id', $queryParams)) {
            V::key('user_id', V::intType())->check($queryParams);
            $optionalParams['userID'] = $queryParams['user_id'];
        }
        if(array_key_exists('resource_type', $queryParams)) {
            V::key('resource_type', V::intVal()->between(0, 12))->check($queryParams);
            $optionalParams['resource'] = LogResource::from($queryParams['resource_type']);
            if(array_key_exists('resource_id', $queryParams)) {
                V::key('resource_id', V::intVal())->check($queryParams);
                $optionalParams['resourceId'] = $queryParams['resource_id'];
            }
        }
        if(array_key_exists('page', $queryParams) && array_key_exists('per_page', $queryParams)) {
            V::key('page', V::intVal())->key('per_page', V::intVal()->between(1, 512))->check($queryParams);
            $optionalParams['page'] = $queryParams['page'];
            $optionalParams['perPage'] = $queryParams['per_page'];
        }
        $logs = $service->getLogs(...$optionalParams);
        $response->getBody()->write(json_encode($logs));
        return $response;
    }
}