<?php
namespace HoneySens\app\controllers;

use HoneySens\app\services\EventsService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Eventdetails extends RESTResource {

    static function registerRoutes($api) {
        // Returns details (including packets) that belong to a certain event
        $api->get('/by-event/{id:\d+}', [Eventdetails::class, 'get']);
        $api->get('/by-archived-event/{id:\d+}', [Eventdetails::class, 'getFromArchive']);
    }

    public function get(Request $request, Response $response, EventsService $service, $id) {
        $this->assureAllowed('get');
        $details = $service->getEventDetails(array('userID' => $this->getSessionUserID(), 'eventID' => $id, 'type' => 0));
        $packets = $service->getEventDetails(array('userID' => $this->getSessionUserID(), 'eventID' => $id, 'type' => 1));
        $result = array('details' => $details, 'packets' => $packets);
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    public function getFromArchive(Request $request, Response $response, EventsService $service, $id) {
        $this->assureAllowed('get');
        $result = $service->getArchivedDetails($id, $this->getSessionUserID());
        $response->getBody()->write(json_encode($result));
        return $response;
    }
}
