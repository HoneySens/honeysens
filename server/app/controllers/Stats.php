<?php
namespace HoneySens\app\controllers;

use HoneySens\app\services\StatsService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Stats extends RESTResource {

    static function registerRoutes($api) {
        $api->get('', [Stats::class, 'get']);
    }

    public function get(Request $request, Response $response, StatsService $service) {
        $this->assureAllowed('get');
        $criteria = $request->getQueryParams();
        $criteria['userID'] = $this->getSessionUserID();
        $result = $service->get($criteria);
        $response->getBody()->write(json_encode($result));
        return $response;
    }
}
