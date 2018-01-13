<?php


class HeaderPageControl extends BaseControl {
    private $page;

    public function __construct(Page $page, BasePresenter $presenter, $name) {
        $this->page = $page;
        parent::__construct($presenter, $name);
    }


    public function render() {
        $page = $this->page;
        $header = $this->getHeaderManager()->getHeader($page->getLang());
        $this->template->header = $header;
        $this->template->isLoggedIn = $this->getPresenter()->getUser()->isLoggedIn();
        /** @var UserIdentity $identity */
        $identity = $this->getPresenter()->getUser()->getIdentity();
        $this->template->username = $identity->getUsername();
        $this->template->page = $page;
        $this->template->render();
    }
}