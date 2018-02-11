<?php


class AdminBarControl extends BaseControl {

    /**
     * @var PageWrapper
     */
    private $page;

    public function __construct(?PageWrapper $page, BasePresenter $presenter, $name) {
        parent::__construct($presenter, $name);
        $this->page = $page;
    }

    /**
     * @throws InvalidState
     */
    public function render() {
        /** @var UserIdentity $identity */
        $identity = $this->getPresenter()->getUser()->getIdentity();
        $this->template->username = $identity->getUsername();
        $this->template->admin_locale = $adminLocale = $this->getPresenter()->getCurrentAdminLocale();

        /* translating admin header in language set in in his identity */
        $translator = $this->getPresenter()->translator;
        $this->template->admin_go_home_label = $translator->translate("go_admin");

        if ($this->page instanceof PageWrapper) {
            $this->template->page = $this->page;
            $this->template->admin_go_edit_label = $translator->translate("edit_page", null, [], null, $adminLocale);
        }
        $this->template->render();
    }
}