<?php
namespace HoneySens\app\models;

use Doctrine\ORM\EntityManager;
use HoneySens\app\models\entities\Template;
use HoneySens\app\models\entities\TemplateOverlay;
use HoneySens\app\models\exceptions\NotFoundException;

/**
 * Management of notification templates. System-wide default templates are defined here. Since these can be
 * overwritten with overlays, this service manages both user-defined overlay data and hardcoded templates
 * to provide a single consistent template API.
 */
class TemplateService {

    private $em;
    private $services;
    private $templates;

    public function __construct($services, EntityManager $em) {
        $this->em = $em;
        $this->services = $services;
        $this->templates = array(
            Template::TYPE_EMAIL_EVENT_NOTIFICATION => new Template(Template::TYPE_EMAIL_EVENT_NOTIFICATION,
                'Ereignis-Benachrichtigung',
                <<<BOUNDARY
Dies ist eine automatisch generierte Nachricht vom HoneySens-System, um auf einen Vorfall innerhalb des Sensornetzwerkes hinzuweisen. Details entnehmen Sie der nachfolgenden Auflistung.

####### Vorfall {{ID}} #######

{{SUMMARY}}

{{DETAILS}}
BOUNDARY
                , array(
                    'ID' => 'Identifikationsnummer des Ereignisses',
                    'SUMMARY' => 'Tabellarische Kurzzusammenfassung des Ereignisses',
                    'DETAILS' => 'Ereignisspezifische Details'),
                array(
                    'ID' => '12345',
                    'SUMMARY' => <<<BOUNDARY
Datum: 12.08.2020
Zeit: 13:26:00
Sensor: Zentrale
Klassifikation: Honeypot-Verbindung
Quelle: 192.168.1.2
Details: SSH
BOUNDARY
                    , 'DETAILS' => <<<BOUNDARY
Sensorinteraktion:
--------------------------
13:26:00: SSH: Connection from 192.168.1.2:48102 
13:26:02: SSH: Invalid login attempt (root/1234)
13:26:03: SSH: Connection closed
BOUNDARY
                ))
        );
        // Fetch and link overlay from the database
        foreach($this->em->getRepository('HoneySens\app\models\entities\TemplateOverlay')->findAll() as $overlay) {
            if(array_key_exists($overlay->getType(), $this->templates)) {
                $this->templates[$overlay->getType()]->setOverlay($overlay);
            }
        };
    }

    /**
     * Returns all raw templates, factoring in potential overlays.
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