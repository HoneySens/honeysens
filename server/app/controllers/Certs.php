<?php
namespace HoneySens\app\controllers;

use HoneySens\app\services\CertsService;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Interfaces\RouteCollectorProxyInterface;

class Certs extends RESTResource {

    static function registerRoutes(RouteCollectorProxyInterface $api): void {
        $api->get('/{id:\d+}', [Certs::class, 'getCert']);
    }

    public function getCert(Response $response, CertsService $service, int $id): Response {
        $this->assureAllowed('get');
        $result = $service->getCerts($this->getSessionUser(), $id);
        $response->getBody()->write(json_encode($result));
        return $response;
    }
}
