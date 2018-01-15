<?php


class PaginatorControl extends BaseControl {

    public function __construct(BasePresenter $presenter, string $name, string $page_key, int $page, int $numberOfPages) {
        parent::__construct($presenter, $name);
        $this->template->page_key = $page_key;
        $this->template->page = $page;
        $this->template->numberOfPages = $numberOfPages;
    }

    public function render() {
        $this->template->render();
    }
}