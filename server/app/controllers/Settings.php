<?php
namespace HoneySens\app\controllers;

use HoneySens\app\services\SettingsService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Settings extends RESTResource {

    static function registerRoutes($api) {
        $api->get('', [Settings::class, 'get']);
        $api->put('', [Settings::class, 'put']);
        $api->post('/testmail', [Settings::class, 'sendtestMail']);
        $api->post('/testevent', [Settings::class, 'sendTestEvent']);
    }

    public function get(Request $request, Response $response, SettingsService $service) {
        $this->assureAllowed('get');
        $settings = $service->get($this->getSessionUserID());
        $response->getBody()->write(json_encode($settings));
        return $response;
    }

    public function put(Request $request, Response $response, SettingsService $service) {
        $this->assureAllowed('update');
        $settings = $service->update($request->getParsedBody());
        $response->getBody()->write(json_encode($settings));
        return $response;
    }

    public function sendtestMail(Request $request, Response $response, SettingsService $service) {
        $this->assureAllowed('update');
        $task = $service->sendTestMail($request->getParsedBody(), $this->getSessionUser());
        $response->getBody()->write(json_encode($task->getState()));
        return $response;
    }

    public function sendTestEvent(Request $request, Response $response, SettingsService $service) {
        $this->assureAllowed('update');
        $response->getBody()->write(json_encode($service->sendTestEvent()));
        return $response;
    }
}