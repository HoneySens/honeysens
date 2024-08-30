<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\Utils;
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

    public function getSystemInfo(Response $response, SystemService $service): Response {
        $response->getBody()->write(json_encode($service->getSystemInfo($this->getServerCert())));
        return $response;
    }

    public function removeAllEvents(Response $response, SystemService $service): Response {
        $service->removeAllEvents($this->getSessionUser());
        $response->getBody()->write(json_encode([]));
        return $response;
    }

    public function refreshCertificates(Response $response, SystemService $service): Response {
        $service->refreshCertificates($this->getSessionUser());
        $response->getBody()->write(json_encode([]));
        return $response;
    }

    public function install(Request $request, Response $response, SystemService $service): Response {
        $data = $request->getParsedBody();
        V::arrayType()
            ->key('email', Utils::emailValidator())
            ->key('password', V::stringType()->length(6, 255))
            ->key('serverEndpoint', V::stringType())
            ->key('divisionName', V::alnum()->length(1, 255))
            ->check($data);
        $systemData = $service->install($data['email'], $data['password'], $data['serverEndpoint'], $data['divisionName']);
        $response->getBody()->write(json_encode($systemData));
        return $response;
    }
}
