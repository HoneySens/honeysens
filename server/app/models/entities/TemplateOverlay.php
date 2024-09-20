<?php
namespace HoneySens\app\models\entities;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;

/**
 * User-defined template overlay.
 */
#[Entity]
#[Table(name: "template_overlays")]
class TemplateOverlay {

    /**
     * For each template, only one overlay can exist.
     * Therefore, template overlays use the 'type' of system templates as their primary key.
     */
    #[Id]
    #[Column(type: Types::INTEGER)]
    protected $type;

    /**
     * Overridden user-defined template content.
     */
    #[Column(type: Types::TEXT)]
    protected $template;

    /**
     * Specifies which system template is overridden.
     *
     * @param int $type
     * @return TemplateOverlay
     */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    /**
     * Returns the type of this template overlay.
     *
     * @return int
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Sets the actual template content, which is a string including some substitution variables.
     *
     * @param string $template
     * @return TemplateOverlay
     */
    public function setTemplate($template) {
        $this->template = $template;
        return $this;
    }

    /**
     * Returns the raw template content.
     *
     * @return string
     */
    public function getTemplate() {
        return $this->template;
    }

    public function getState() {
        return array(
            'type' => $this->getType(),
            'template' => $this->getTemplate()
        );
    }
}
