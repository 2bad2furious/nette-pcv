<?php


class BreadCrumbsControl extends BaseControl {
    private $page;

    public function __construct(PageWrapper $page, BasePresenter $presenter, $name) {
        $this->page = $page;
        parent::__construct($presenter, $name);
    }


    public function render() {
        if ($this->page->getDisplayBreadCrumbs()) {


            $p = $this->page;

            $path = [$p];
            while (($p = $p->getParent()) instanceof PageWrapper) {
                array_unshift($path, $p);
            }

            $this->template->path = $path;
            $this->template->render();
        }
    }
}