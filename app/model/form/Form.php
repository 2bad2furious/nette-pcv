<?php


class Form extends \Nette\Application\UI\Form {
    use TForm;


    public function addContainer($name) {
        $control = new FormContainer;
        $control->currentGroup = $this->currentGroup;
        if ($this->currentGroup !== null) {
            $this->currentGroup->add($control);
        }
        return $this[$name] = $control;
    }
}