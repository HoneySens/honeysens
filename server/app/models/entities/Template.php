<?php
namespace HoneySens\app\models\entities;

use HoneySens\app\models\constants\TemplateType;

/**
 * Textual system template with token substitution for E-Mail notifications.
 * Can be overridden by a TemplateOverlay.
 */
class Template {

    /**
     * The notification type this template is used for.
     */
    public TemplateType $type;

    /**
     * Informal name of this template.
     */
    public string $name;

    /**
     * Template content: A string including substitution variables.
     * Templates use the following string substitutions to fill in dynamic parts:
     * {{SUMMARY}} - Key-value-style summary of basic event properties
     */
    public string $template;

    /**
     * Associative array of substitution variables available for this template with a
     * descriptive textual summary of the variable's purpose.
     */
    public array $variables = array();

    /**
     * Preview/example data for the substitution variables defined in $variables.
     */
    public array $preview = array();

    /**
     * An optional user-defined overlay for this template.
     */
    public ?TemplateOverlay $overlay = null;

    public function __construct(TemplateType $type, string $name, string $template, array $variables, array $preview) {
        $this->type = $type;
        $this->name = $name;
        $this->template = $template;
        $this->variables = $variables;
        $this->preview = $preview;
    }

    /**
     * Returns the current template content, factoring in a potential overlay.
     */
    public function getActiveTemplate(): string {
        return $this->overlay === null ? $this->template : $this->overlay->template;
    }

    public function getState(): array {
        return array(
            'type' => $this->type->value,
            'name' => $this->name,
            'template' => $this->template,
            'variables' => $this->variables,
            'preview' => $this->preview,
            'overlay' => $this->overlay?->getState()
        );
    }
}
