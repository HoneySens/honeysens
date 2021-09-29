<?php
namespace HoneySens\app\models\entities;

/**
 * Class Template
 *
 * Textual system template with token substitution for E-Mail notifications.
 * Can be overridden by a TemplateOverlay.
 *
 * @package HoneySens\app\models\entities
 */
class Template {

    const TYPE_EMAIL_EVENT_NOTIFICATION = 0;
    const TYPE_TEST = 1;

    /**
     * Template type (message type this template applies to)
     *
     * @var integer
     */
    protected $type;

    /**
     * Informal name of this template.
     *
     * @var string
     */
    protected $name;

    /**
     * Template content. Templates use the following string substitutions to fill in dynamic parts:
     * {{SUMMARY}} - Key-value-style summary of basic event properties (
     *
     * @var string
     */
    protected $template;

    /**
     * Associative array of substitution variables available for this template and a respective textual
     * summary what the variable contains.
     *
     * @var array
     */
    protected $variables = array();

    /**
     * Preview/example data for the substitution variables defined in $variables.
     *
     * @var array
     */
    protected $preview = array();

    /**
     * An optional user-defined overlay for this template.
     *
     * @var TemplateOverlay
     */
    protected $overlay = null;

    public function __construct($type, $name, $template, array $variables, array $preview) {
        $this->type = $type;
        $this->name = $name;
        $this->template = $template;
        $this->variables = $variables;
        $this->preview = $preview;
    }

    /**
     * The template type specifies for which notification type this template is used.
     *
     * @param int $type
     * @return Template
     */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }

    /**
     * Returns the type of this template.
     *
     * @return int
     */
    public function getType() {
        return $this->type;
    }

    /**
     * Set the template name.
     *
     * @param string $name
     * @return Template
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * Returns the template name.
     *
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Sets the actual template content, which is a string including some substitution variables.
     *
     * @param string $template
     * @return Template
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

    /**
     * Returns the template content, factoring in a potential overlay.
     *
     * @return string
     */
    public function getActiveTemplate() {
        return $this->getOverlay() == null ? $this->getTemplate() : $this->getOverlay()->getTemplate();
    }

    /**
     * Sets the available template variables for this template with a string description for each.
     *
     * @param array $variables
     * @return Template
     */
    public function setVariables($variables) {
        $this->variables = $variables;
        return $this;
    }

    /**
     * Returns the available template variables and their descriptions.
     *
     * @return array
     */
    public function getVariables() {
        return $this->variables;
    }

    /**
     * Sets preview data for this template.
     *
     * @param array $preview
     * @return Template
     */
    public function setPreview($preview) {
        $this->preview = $preview;
        return $this;
    }

    /**
     * Returns preview data for this template.
     *
     * @return array
     */
    public function getPreview() {
        return $this->preview;
    }
    /**
     * Sets an overlay for this template or removes it (by passing null).
     *
     * @param TemplateOverlay|null $overlay
     * @return $this
     */
    public function setOverlay(TemplateOverlay $overlay = null) {
        $this->overlay = $overlay;
        return $this;
    }

    /**
     * Returns the overlay for this template, if any (null otherwise).
     *
     * @return TemplateOverlay|null
     */
    public function getOverlay() {
        return $this->overlay;
    }

    public function getState() {
        return array(
            'type' => $this->getType(),
            'name' => $this->getName(),
            'template' => $this->getTemplate(),
            'variables' => $this->getVariables(),
            'preview' => $this->getPreview(),
            'overlay' => $this->getOverlay() ? $this->getOverlay()->getState() : null
        );
    }
}