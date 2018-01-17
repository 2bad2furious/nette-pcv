<?php


class HeaderPageControl extends BaseControl {
    private $page;

    public function __construct(PageWrapper $page, BasePresenter $presenter, $name) {
        $this->page = $page;
        parent::__construct($presenter, $name);
    }


    public function render() {
        $page = $this->page;
        $header = $this->getHeaderManager()->getHeader($page->getLanguageId());
        $this->template->header = $header;
        $this->template->isLoggedIn = $this->getPresenter()->getUser()->isLoggedIn();
        /** @var UserIdentity $identity */
        $identity = $this->getPresenter()->getUser()->getIdentity();
        $this->template->username = $identity->getUsername();
        $this->template->page = $page;
        $this->template->admin_locale = $adminLocale = $this->getPresenter()->getCurrentAdminLocale();

        /* translating admin header in language set in in his identity */
        $translator = $this->getPresenter()->translator;
        $this->template->admin_go_edit_label = $translator->translate("edit_page", null, [], null, $adminLocale);
        $this->template->admin_go_home_label = $translator->translate("go_admin");


        $this->template->render();
    }
}