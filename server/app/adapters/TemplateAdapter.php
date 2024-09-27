<?php
namespace HoneySens\app\adapters;

use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Exception\ORMException;
use HoneySens\app\models\constants\TemplateType;
use HoneySens\app\models\entities\Template;
use HoneySens\app\models\entities\TemplateOverlay;
use HoneySens\app\models\exceptions\NotFoundException;
use HoneySens\app\models\exceptions\SystemException;
use Predis;

/**
 * Management of notification templates. System-wide default templates are defined here. Since these can be
 * overwritten with overlays, this service manages both user-defined overlay data and hardcoded templates
 * to provide a single consistent template API.
 */
class TemplateAdapter {

    private EntityManager $em;
    private array $templates;

    public function __construct(EntityManager $em) {
        $this->em = $em;
        $this->templates = array();
        // Fetch default templates from broker
        $broker = new Predis\Client(array(
            'scheme' => 'tcp',
            'host' => getenv('HS_BROKER_HOST'),
            'port' => getenv('HS_BROKER_PORT')
        ));
        foreach(json_decode($broker->get('templates'), true) as $type => $template)
            $this->templates[$type] = new Template(
                TemplateType::from($type), $template['name'], $template['template'], $template['variables'], $template['preview']);
        // Fetch and link overlays from the database
        try {
            foreach ($this->em->getRepository('HoneySens\app\models\entities\TemplateOverlay')->findAll() as $overlay) {
                if (array_key_exists($overlay->type->value, $this->templates)) {
                    $this->templates[$overlay->type->value]->overlay = $overlay;
                }
            };
        } catch(TableNotFoundException) {
            // May happen if the DB hasn't been initialized yet, which we can safely ignore
        }
    }

    /**
     * Returns all templates, factoring in potential overlays.
     */
    public function getTemplates(): array {
        return array_values($this->templates);
    }

    /**
     * Returns a specific template.
     *
     * @param TemplateType $type The type of the template to return
     * @throws NotFoundException
     */
    public function getTemplate(TemplateType $type): Template {
        if(in_array($type->value, array_keys($this->templates), true)) {
            return $this->templates[$type->value];
        } else throw new NotFoundException();
    }

    /**
     * Sets the given overlay string (or null) as new overlay for a specific template type.
     *
     * @param TemplateType $type The type of the template to update with the new overlay
     * @param string|null $overlay New overlay content or null to remove any existing overlay
     * @throws NotFoundException
     * @throws SystemException
     */
    public function setOverlay(TemplateType $type, ?string $overlay): void {
        $template = $this->getTemplate($type);
        try {
            if ($template->overlay != null) {
                // Update/delete existing overlay
                if ($overlay != null) {
                    $template->overlay->template = $overlay;
                } else {
                    $this->em->remove($template->overlay);
                    $template->overlay = null;
                }
            } elseif ($overlay != null) {
                // Add new overlay
                $templateOverlay = new TemplateOverlay();
                $templateOverlay->type = $type;
                $templateOverlay->template = $overlay;
                $this->em->persist($templateOverlay);
                $template->overlay = $templateOverlay;
            }
            $this->em->flush();
        } catch(ORMException $e) {
            throw new SystemException($e);
        }
    }

    /**
     * Processes the given template by substituting all template variables with the values set in $data
     * and returning the result.
     *
     * @param TemplateType $type The type of the template to process
     * @param array $data Substitution data for the template variables
     * @return string
     */
    public function processTemplate(TemplateType $type, array $data): string {
       $template = $this->templates[$type->value];
       $result = $template->getActiveTemplate();
       foreach($data as $var => $val) $result = str_replace('{{' . $var . '}}', $val, $result);
       return $result;
    }
}
