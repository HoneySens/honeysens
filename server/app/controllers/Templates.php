<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\entities\LogEntry;
use HoneySens\app\models\entities\Template;
use HoneySens\app\models\ServiceManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Respect\Validation\Validator as V;
use \Slim\Routing\RouteCollectorProxy;

class Templates extends RESTResource {

    static function registerRoutes($templates, $em, $services, $config) {
        $templates->get('', function (Request $request, Response $response) use ($em, $services, $config) {
            $controller = new Templates($em, $services, $config);
            $response->getBody()->write(json_encode($controller->get()));
            return $response;
        });

        $templates->put('/{id:\d+}', function (Request $request, Response $response, array $args) use ($em, $services, $config) {
            $controller = new Templates($em, $services, $config);
            $template = $controller->update(intval($args['id']), $request->getParsedBody());
            $response->getBody()->write(json_encode($template->getState()));
            return $response;
        });
    }

    /**
     * Returns all templates and corresponding overlays.
     *
     * @return array
     * @throws \HoneySens\app\models\exceptions\ForbiddenException
     */
    public function get() {
        $this->assureAllowed('get', 'settings');
        $templates = array();
        foreach($this->getServiceManager()->get(ServiceManager::SERVICE_TEMPLATE)->getTemplates() as $template) {
            $templates[] = $template->getState();
        }
        return $templates;
    }

    /**
     * Registers a template overwrite for the given template type.
     *
     * Expects the following parameters:
     * - template: User-supplied template string or null to remove the overlay
     *
     * @param int $type
     * @param array $data
     * @return Template
     * @throws \HoneySens\app\models\exceptions\ForbiddenException
     */
    public function update($type, $data) {
        $this->assureAllowed('update', 'settings');
        $templateService = $this->getServiceManager()->get(ServiceManager::SERVICE_TEMPLATE);
        // Validation
        V::intType()->check($type);
        V::arrayType()
            ->key('template', V::optional(V::stringType()))
            ->check($data);
        // Persistence
        $template = $templateService->getTemplate($type);
        // Since V::optional() accepts both null and '', we deliberately ignore types in the following comparison
        if($data['template'] == null) {
            $templateService->setOverlay($type, null);
            $this->log(sprintf('Template "%s" (ID %s) reset to system default',
                $template->getName(), $template->getType()), LogEntry::RESOURCE_SETTINGS, $template->getType());
        } else {
            $templateService->setOverlay($type, $data['template']);
            $this->log(sprintf('Template "%s" (ID %s) updated with custom content',
                $template->getName(), $template->getType()), LogEntry::RESOURCE_SETTINGS, $template->getType());
        }
        return $template;
    }
}
