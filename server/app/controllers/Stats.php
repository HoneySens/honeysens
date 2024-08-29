<?php
namespace HoneySens\app\controllers;

use HoneySens\app\services\StatsService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;

class Stats extends RESTResource {

    static function registerRoutes($api) {
        $api->get('', [Stats::class, 'get']);
    }

    public function get(Request $request, Response $response, StatsService $service): Response {
        $this->assureAllowed('get');
        $criteria = $request->getQueryParams();
        $year = V::key('year', V::intVal()->between(1970, 2200))->validate($criteria) ? $criteria['year'] : null;
        $month = V::key('month', V::intVal()->between(0, 12))->validate($criteria) ? $criteria['month'] : null;
        $divisionID = V::key('division', V::intVal())->validate($criteria) ? $criteria['division'] : null;
        $result = $service->get($this->getSessionUser(), $divisionID, $year, $month);
        $response->getBody()->write(json_encode($result));
        return $response;
    }
}
