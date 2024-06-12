<?php
namespace HoneySens\app\controllers;

use HoneySens\app\services\DivisionsService;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Interfaces\RouteCollectorProxyInterface;

/**
 * Class Contacts
 * @package HoneySens\app\controllers
 *
 * Incident contact retrieval. Contact creation and updates are handled
 * by the division controller, because contacts always belong to a certain division.
 */
class Contacts extends RESTResource {

    static function registerRoutes(RouteCollectorProxyInterface $api): void {
        $api->get('[/{id:\d+}]', [Contacts::class, 'get']);
    }

    public function get(Response $response, DivisionsService $service, $id = null): Response {
        $this->assureAllowed('get');
        $result = $service->getContact($this->getSessionUser(), $id);
        $response->getBody()->write(json_encode($result));
        return $response;
    }
}
