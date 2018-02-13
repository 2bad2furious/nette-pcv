<?php


class HeaderPageControl extends BaseControl {
    private $page;

    public function __construct(PageWrapper $page, BasePresenter $presenter, $name) {
        $this->page = $page;
        parent::__construct($presenter, $name);
    }


    public function render() {
        $this->template->page = $this->page;
        $this->template->header = $this->getHeaderManager()->getHeader($this->page->getLanguageId());
        if ($this->template->header)
            $this->template->render();
    }
}