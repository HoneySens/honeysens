<?php
namespace HoneySens\app\models\entities;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\Table;
use HoneySens\app\models\constants\TemplateType;

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
    #[Column()]
    public TemplateType $type;

    /**
     * Overridden user-defined template content, which is a string including substitution variables.
     */
    #[Column(type: Types::TEXT)]
    public string $template;

    public function getState(): array {
        return array(
            'type' => $this->type->value,
            'template' => $this->template
        );
    }
}
