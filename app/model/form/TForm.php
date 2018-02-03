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

    public function addText($name, $label = null, $cols = null, $maxLength = null) {
        return $this->addBootstrapClass(parent::addText($name, $label, $cols, $maxLength));
    }

    public function addPassword($name, $label = null, $cols = null, $maxLength = null) {
        return $this->addBootstrapClass(parent::addPassword($name, $label, $cols, $maxLength));
    }

    public function addTextArea($name, $label = null, $cols = null, $rows = null) {
        return $this->addBootstrapClass(parent::addTextArea($name, $label, $cols, $rows));
    }

    public function addEmail($name, $label = null) {
        return $this->addBootstrapClass(parent::addEmail($name, $label));
    }

    public function addInteger($name, $label = null) {
        return $this->addBootstrapClass(parent::addInteger($name, $label));
    }

    protected function addBootstrapClass(\Nette\Forms\Controls\TextBase $base) {
        $base->getControlPrototype()->class(self::$BOOTSTRAP_FORM_CONTROL_CLASS);
        return $base;
    }

    public function addSubmit($name, $caption = null) {
        /** @var SubmitButton $base */
        $base = parent::addSubmit($name, $caption);
        $base->getControlPrototype()->class("btn submit");
        return $base;
    }

    /**
     * Adds select box control that allows single item selection.
     * @param  string
     * @param  string|object
     * @param  array
     * @param  int
     * @return SelectBox
     */
    public function addSelect($name, $label = null, array $items = null, $size = null) {
        //TODO remove this with 3.0
        $base = parent::addSelect($name, (($t = $this->getTranslator()) instanceof \Nette\Localization\ITranslator ? $t->translate($label) : $label), $items, $size)
            ->setTranslator();//purposely unset translator so that the options dont get translated
        return $base;
    }
}