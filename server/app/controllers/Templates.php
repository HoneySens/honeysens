<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\constants\TemplateType;
use HoneySens\app\services\TemplatesService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;

class Templates extends RESTResource {

    static function registerRoutes($api) {
        $api->get('', [Templates::class, 'get']);
        $api->put('/{id:\d+}', [Templates::class, 'put']);
    }

    public function get(Response $response, TemplatesService $service): Response {
        $this->assureAllowed('get', 'settings');
        $response->getBody()->write(json_encode($service->get()));
        return $response;
    }

    public function put(Request $request, Response $response, TemplatesService $service, int $id): Response {
        $this->assureAllowed('update', 'settings');
        $templateType = TemplateType::tryFrom($id);
        V::notEmpty()->check($templateType);
        $data = $request->getParsedBody();
        V::arrayType()->key('template', V::optional(V::stringType()))->check($data);
        $template = $service->update($templateType, $data['template']);
        $response->getBody()->write(json_encode($template->getState()));
        return $response;
    }
}
