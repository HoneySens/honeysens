<?php
namespace HoneySens\app\adapters;

use Doctrine\DBAL\Exception\TableNotFoundException;
use Doctrine\ORM\EntityManager;
use HoneySens\app\models\entities\Template;
use HoneySens\app\models\entities\TemplateOverlay;
use HoneySens\app\models\exceptions\NotFoundException;
use Predis;

/**
 * Management of notification templates. System-wide default templates are defined here. Since these can be
 * overwritten with overlays, this service manages both user-defined overlay data and hardcoded templates
 * to provide a single consistent template API.
 */
class TemplateAdapter {

    private $em;
    private $templates;

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
                $type, $template['name'], $template['template'], $template['variables'], $template['preview']);
        // Fetch and link overlays from the database
        try {
            foreach ($this->em->getRepository('HoneySens\app\models\entities\TemplateOverlay')->findAll() as $overlay) {
                if (array_key_exists($overlay->getType(), $this->templates)) {
                    $this->templates[$overlay->getType()]->setOverlay($overlay);
                }
            };
        } catch(TableNotFoundException) {
            // May happen if the DB hasn't been initialized yet, which we can safely ignore
        }
    }

    /**
     * Returns all templates, factoring in potential overlays.
     *
     * @return array
     */
    public function getTemplates() {
        return array_values($this->templates);
    }

    /**
     * Returns the template of the given type.
     *
     * @param int $type
     * @return Template|null
     * @throws NotFoundException
     */
    public function getTemplate($type) {
        if(in_array($type, array_keys($this->templates), true)) {
            return $this->templates[$type];
        } else throw new NotFoundException();
    }

    /**
     * Sets the given overlay string (or null) as the overlay for a given template type.
     *
     * @param int $type
     * @param string|null $overlay
     * @throws NotFoundException
     */
    public function setOverlay($type, $overlay) {
        $template = $this->getTemplate($type);
        if($template->getOverlay() != null) {
            // Update/delete existing overlay
            if($overlay != null) {
                $template->getOverlay()->setTemplate($overlay);
            } else {
                $this->em->remove($template->getOverlay());
                $template->setOverlay(null);
            }
        } elseif($overlay != null) {
            // Add new overlay
            $templateOverlay = new TemplateOverlay();
            $templateOverlay->setType($type)->setTemplate($overlay);
            $this->em->persist($templateOverlay);
            $template->setOverlay($templateOverlay);
        }
        $this->em->flush();
    }

    /**
     * Processes the given template by substituting all template variables with the values set in $data
     * and returning the result.
     *
     * @param $type
     * @param array $data
     * @return string
     */
    public function processTemplate($type, array $data) {
       $template = $this->templates[$type];
       $result = $template->getActiveTemplate();
       foreach($data as $var => $val) $result = str_replace('{{' . $var . '}}', $val, $result);
       return $result;
    }
}
