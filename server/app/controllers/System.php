<?php
namespace HoneySens\app\controllers;

use HoneySens\app\services\SystemService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;

class System extends RESTResource {

    static function registerRoutes($api) {
        $api->get('', [System::class, 'getSystemInfo']);
        $api->get('/identify', function(Request $request, Response $response) {
            // Predictable endpoint used to test the server's reachability (useful to figure out if a proxy actually works)
            $response->getBody()->write("HoneySens");
            return $response;
        });
        $api->delete('/events', [System::class, 'removeAllEvents']);
        $api->put('/ca', [System::class, 'refreshCertificates']);
        $api->post('/install', [System::class, 'install']);
    }

    public function getSystemInfo(Request $request, Response $response, SystemService $service) {
        $response->getBody()->write(json_encode($service->getSystemInfo()));
        return $response;
    }

    public function removeAllEvents(Request $request, Response $response, SystemService $service) {
        $service->removeAllEvents();
        $response->getBody()->write(json_encode([]));
        return $response;
    }

    public function refreshCertificates(Request $request, Response $response, SystemService $service) {
        $service->refreshCertificates();
        $response->getBody()->write(json_encode([]));
        return $response;
    }

    public function install(Request $request, Response $response, SystemService $service) {
        $request = $request->getBody()->getContents();
        V::json()->check($request);
        $installData = json_decode($request);
        $systemData = $service->install($installData);
        $response->getBody()->write(json_encode($systemData));
        return $response;
    }
}