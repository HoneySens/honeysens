<?php
namespace HoneySens\app\services;

use Doctrine\ORM\EntityManager;
use HoneySens\app\adapters\TemplateAdapter;
use HoneySens\app\models\constants\LogResource;
use HoneySens\app\models\constants\TemplateType;
use HoneySens\app\models\entities\Template;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\models\exceptions\SystemException;

class TemplatesService extends Service {

    private LogService $logger;
    private TemplateAdapter $templateAdapter;

    public function __construct(EntityManager $em, LogService $logger, TemplateAdapter $templateAdapter) {
        parent::__construct($em);
        $this->logger = $logger;
        $this->templateAdapter = $templateAdapter;
    }

    /**
     * Returns all templates and corresponding overlays.
     */
    public function get(): array {
        $templates = array();
        foreach($this->templateAdapter->getTemplates() as $template) {
            $templates[] = $template->getState();
        }
        return $templates;
    }

    /**
     * Registers a template override for the given template type.
     *
     * @param TemplateType $type The kind of template to overwrite
     * @param string|null $content New template content. If null, the template type is reset to the system default.
     * @throws NotFoundException
     * @throws SystemException
     */
    public function update(TemplateType $type, ?string $content): Template {
        $template = $this->templateAdapter->getTemplate($type);
        if($content === null) {
            $this->templateAdapter->setOverlay($type, null);
            $this->logger->log(sprintf('Template "%s" (ID %s) reset to system default',
                $template->getName(), $template->getType()), LogResource::SETTINGS, $template->getType());
        } else {
            $this->templateAdapter->setOverlay($type, $content);
            $this->logger->log(sprintf('Template "%s" (ID %s) updated with custom content',
                $template->getName(), $template->getType()), LogResource::SETTINGS, $template->getType());
        }
        return $template;
    }
}
