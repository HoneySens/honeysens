<?php
namespace HoneySens\app\controllers;

use HoneySens\app\models\entities\LogEntry;
use HoneySens\app\models\ServiceManager;
use Respect\Validation\Validator as V;

class Templates extends RESTResource {

    static function registerRoutes($app, $em, $services, $config, $messages) {
        $app->get('/api/templates/', function () use ($app, $em, $services, $config, $messages) {
            $controller = new Templates($em, $services, $config);
            $templates = $controller->get();
            echo json_encode($templates);
        });

        $app->put('/api/templates/:id', function ($id) use ($app, $em, $services, $config, $messages) {
            $controller = new Templates($em, $services, $config);
            $request = $app->request()->getBody();
            V::json()->check($request);
            $templateData = $controller->update(intval($id), json_decode($request));
            echo json_encode($templateData);
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
     * @param \stdClass $data
     * @throws \HoneySens\app\models\exceptions\ForbiddenException
     */
    public function update($type, $data) {
        $this->assureAllowed('update', 'settings');
        $templateService = $this->getServiceManager()->get(ServiceManager::SERVICE_TEMPLATE);
        // Validation
        V::intType()->check($type);
        V::objectType()->attribute('template', V::optional(V::stringType()))->check($data);
        // Persistence
        $template = $templateService->getTemplate($type);
        // Since V::optional() accepts both null and '', we deliberately ignore types in the following comparison
        if($data->template == null) {
            $templateService->setOverlay($type, null);
            $this->log(sprintf('Template "%s" (ID %s) reset to system default',
                $template->getName(), $template->getType()), LogEntry::RESOURCE_SETTINGS, $template->getType());
        } else {
            $templateService->setOverlay($type, $data->template);
            $this->log(sprintf('Template "%s" (ID %s) updated with custom content',
                $template->getName(), $template->getType()), LogEntry::RESOURCE_SETTINGS, $template->getType());
        }
        return $template->getState();
    }
}