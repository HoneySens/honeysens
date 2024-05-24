<?php
namespace HoneySens\app\services;

use HoneySens\app\adapters\TemplateAdapter;
use HoneySens\app\models\entities\LogEntry;
use HoneySens\app\models\entities\Template;
use Respect\Validation\Validator as V;

class TemplatesService {

    private LogService $logger;
    private TemplateAdapter $templateAdapter;

    public function __construct(LogService $logger, TemplateAdapter $templateAdapter) {
        $this->logger = $logger;
        $this->templateAdapter = $templateAdapter;
    }

    /**
     * Returns all templates and corresponding overlays.
     *
     * @return array
     * @throws \HoneySens\app\models\exceptions\ForbiddenException
     */
    public function get() {
        $templates = array();
        foreach($this->templateAdapter->getTemplates() as $template) {
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
        // Validation
        V::intType()->check($type);
        V::arrayType()
            ->key('template', V::optional(V::stringType()))
            ->check($data);
        // Persistence
        $template = $this->templateAdapter->getTemplate($type);
        // Since V::optional() accepts both null and '', we deliberately ignore types in the following comparison
        if($data['template'] == null) {
            $this->templateAdapter->setOverlay($type, null);
            $this->logger->log(sprintf('Template "%s" (ID %s) reset to system default',
                $template->getName(), $template->getType()), LogEntry::RESOURCE_SETTINGS, $template->getType());
        } else {
            $this->templateAdapter->setOverlay($type, $data['template']);
            $this->logger->log(sprintf('Template "%s" (ID %s) updated with custom content',
                $template->getName(), $template->getType()), LogEntry::RESOURCE_SETTINGS, $template->getType());
        }
        return $template;
    }
}
