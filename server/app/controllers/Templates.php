<?php
namespace HoneySens\app\controllers;

use HoneySens\app\services\TemplatesService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class Templates extends RESTResource {

    static function registerRoutes($api) {
        $api->get('', [Templates::class, 'get']);
        $api->put('/{id:\d+}', [Templates::class, 'put']);
    }

    public function get(Request $request, Response $response, TemplatesService $service) {
        $this->assureAllowed('get', 'settings');
        $response->getBody()->write(json_encode($service->get()));
        return $response;
    }

    public function put(Request $request, Response $response, TemplatesService $service, $id) {
        $this->assureAllowed('update', 'settings');
        $template = $service->update(intval($id), $request->getParsedBody());
        $response->getBody()->write(json_encode($template->getState()));
        return $response;
    }
}