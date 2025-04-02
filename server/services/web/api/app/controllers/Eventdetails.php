<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\constants\EventDetailType;
use HoneySens\app\services\EventsService;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Interfaces\RouteCollectorProxyInterface;

class Eventdetails extends RESTResource {

    static function registerRoutes(RouteCollectorProxyInterface $api): void {
        // Returns details (including packets) that belong to a certain event
        $api->get('/by-event/{id:\d+}', [Eventdetails::class, 'getEventDetails']);
        $api->get('/by-archived-event/{id:\d+}', [Eventdetails::class, 'getFromArchive']);
    }

    public function getEventDetails(Response $response, EventsService $service, int $id): Response {
        $this->assureAllowed('get');
        $result = array(
            'details' => $service->getEventDetails($this->getSessionUser(), EventDetailType::GENERIC, $id),
            'packets' => $service->getEventDetails($this->getSessionUser(), EventDetailType::INTERACTION, $id)
        );
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function getFromArchive(Response $response, EventsService $service, int $id): Response {
        $this->assureAllowed('get');
        $result = $service->getArchivedDetails($this->getSessionUser(), $id);
        $response->getBody()->write(json_encode($result));
        return $response;
    }
}
