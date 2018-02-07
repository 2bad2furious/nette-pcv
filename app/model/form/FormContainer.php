<?php


use Nette\Forms\Container;

class FormContainer extends Container {
    use TForm;

    /**
     * @return \Nette\Localization\ITranslator|null
     */
    protected function getTranslator() {
        return $this->getForm()->getTranslator();
    }
}