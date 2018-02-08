<?php


use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\SubmitButton;

trait TForm {

    /**
     * @return \Nette\Localization\ITranslator|null
     */
    protected abstract function getTranslator();

    //cant have constants what? xd
    private static $BOOTSTRAP_FORM_CONTROL_CLASS = "form-control";

    public function addText($name, $label = null, $cols = null, $maxLength = null, bool $bootstrap = true) {
        $el = parent::addText($name, $label, $cols, $maxLength);
        return $bootstrap ? $this->addBootstrapClass($el) : $el;
    }

    public function addPassword($name, $label = null, $cols = null, $maxLength = null, bool $bootstrap = true) {
        $el = parent::addPassword($name, $label, $cols, $maxLength);
        return $bootstrap ? $this->addBootstrapClass($el) : $el;
    }

    public function addTextArea($name, $label = null, $cols = null, $rows = null, bool $bootstrap = true) {
        $el = parent::addTextArea($name, $label, $cols, $rows);
        return $bootstrap ? $this->addBootstrapClass($el) : $el;
    }

    public function addEmail($name, $label = null, bool $bootstrap = true) {
        $el = parent::addEmail($name, $label);
        return $bootstrap ? $this->addBootstrapClass($el) : $el;
    }

    public function addInteger($name, $label = null, bool $bootstrap = true) {
        $el = parent::addInteger($name, $label);
        return $bootstrap ? $this->addBootstrapClass($el) : $el;
    }

    protected function addBootstrapClass(\Nette\Forms\Controls\TextBase $base) {
        $base->getControlPrototype()->class(self::$BOOTSTRAP_FORM_CONTROL_CLASS);
        return $base;
    }

    public function addSubmit($name, $caption = null, bool $bootstrap = true, string $iconHtml = "<i class=\"fa fa-save\"></i>") {
        /** @var SubmitButton $base */
        $base = parent::addSubmit($name, $caption);
        if ($bootstrap) {
            $base->getControlPrototype()
                ->setName("button")
                ->setHtml($iconHtml . " " . $this->getTranslator()->translate($caption))
                ->class("btn submit");
        }
        return $base;
    }


    public function addCheckbox($name, $caption = null, bool $bootstrap = true) {
        $el = parent::addCheckbox($name, $caption);
        if ($bootstrap) $el->getControlPrototype()->class("switch");
        return $el;
    }

    /**
     * Adds select box control that allows single item selection.
     * @param  string
     * @param  string|object
     * @param  array
     * @param  int
     * @return SelectBox
     */
    public function addSelect($name, $label = null, array $items = null, $size = null, $bootstrap = true) {
        //TODO remove this with 3.0
        $base = parent::addSelect($name, (($t = $this->getTranslator()) instanceof \Nette\Localization\ITranslator ? $t->translate($label) : $label), $items, $size)
            ->setTranslator();//purposely unset translator so that the options dont get translated
        if ($bootstrap) $base->getControlPrototype()->class("selectmenu");
        return $base;
    }

}