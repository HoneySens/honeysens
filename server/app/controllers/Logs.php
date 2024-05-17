<?php
namespace HoneySens\app\controllers;

use HoneySens\app\services\LogService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Logs extends RESTResource {

    static function registerRoutes($api) {
        $api->get('/', [Logs::class, 'get']);
    }

    public function get(Request $request, Response $response, LogService $service) {
        $this->assureAllowed('get');
        $logs = $service->get($request->getQueryParams());
        $response->getBody()->write(json_encode($logs));
        return $response;
    }
}