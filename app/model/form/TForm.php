<?php


trait TForm {

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
        /** @var \Nette\Forms\Controls\SubmitButton $base */
        $base = parent::addSubmit($name, $caption);
        $base->getControlPrototype()->class("btn submit");
        return $base;
    }
}