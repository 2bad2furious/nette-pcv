<?php


class BreadCrumbsControl extends BaseControl {
    private $page;

    public function __construct(Page $page, BasePresenter $presenter, $name) {
        $this->page = $page;
        parent::__construct($presenter, $name);
    }


    public function render() {
        $this->template->page = $this->page;
        $this->template->render();
    }
}