<?php


abstract class BaseControl extends \Nette\Application\UI\Control {
    public function __construct(BasePresenter $presenter, $name) {
        parent::__construct($presenter, $name);
        $this->initTranslator();
        $this->template->setFile(__DIR__ . "/templates/" . $this->getName() . ".latte");
    }

    protected function initTranslator() {
        $this->template->setTranslator($this->getPresenter()->translator);
    }
}