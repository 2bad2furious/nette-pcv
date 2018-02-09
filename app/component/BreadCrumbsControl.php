<?php


class BreadCrumbsControl extends BaseControl {
    private $page;

    public function __construct(PageWrapper $page, BasePresenter $presenter, $name) {
        $this->page = $page;
        parent::__construct($presenter, $name);
    }


    public function render() {
        if ($this->page->getDisplayBreadCrumbs()) {
            $this->template->page = $this->page;
            $this->template->render();
        }
    }
}