<?php
namespace HoneySens\app\services;

use Doctrine\ORM\EntityManager;
use HoneySens\app\models\entities\LogEntry;
use HoneySens\app\models\entities\Template;
use HoneySens\app\models\ServiceManager;
use HoneySens\app\services\LogService;
use NoiseLabs\ToolKit\ConfigParser\ConfigParser;
use Respect\Validation\Validator as V;

class TemplatesService {

    private ConfigParser $config;
    private EntityManager $em;
    private LogService $logger;
    private ServiceManager $serviceManager;

    public function __construct(ConfigParser $config, EntityManager $em, LogService $logger, ServiceManager $serviceManager) {
        $this->config = $config;
        $this->em= $em;
        $this->logger = $logger;
        $this->serviceManager = $serviceManager;
    }

    /**
     * Returns all templates and corresponding overlays.
     *
     * @return array
     * @throws \HoneySens\app\models\exceptions\ForbiddenException
     */
    public function get() {
        $templates = array();
        foreach($this->serviceManager->get(ServiceManager::SERVICE_TEMPLATE)->getTemplates() as $template) {
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
    public function update(int $type, $data) {
        $templateService = $this->serviceManager->get(ServiceManager::SERVICE_TEMPLATE);
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
            $this->logger->log(sprintf('Template "%s" (ID %s) reset to system default',
                $template->getName(), $template->getType()), LogEntry::RESOURCE_SETTINGS, $template->getType());
        } else {
            $templateService->setOverlay($type, $data['template']);
            $this->logger->log(sprintf('Template "%s" (ID %s) updated with custom content',
                $template->getName(), $template->getType()), LogEntry::RESOURCE_SETTINGS, $template->getType());
        }
        return $template;
    }
}