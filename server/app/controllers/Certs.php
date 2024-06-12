<?php
namespace HoneySens\app\controllers;

use HoneySens\app\services\CertsService;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Interfaces\RouteCollectorProxyInterface;

class Certs extends RESTResource {

    static function registerRoutes(RouteCollectorProxyInterface $api): void {
        $api->get('/{id:\d+}', [Certs::class, 'get']);
    }

    public function get(Response $response, CertsService $service, int $id): Response {
        $this->assureAllowed('get');
        $result = $service->get($this->getSessionUser(), $id);
        $response->getBody()->write(json_encode($result));
        return $response;
    }
}
