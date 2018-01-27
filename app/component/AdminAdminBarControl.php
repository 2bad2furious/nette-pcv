<?php


class AdminAdminBarControl extends BaseControl {
    private $pageWrapper;

    public static function createPageBar(PageWrapper $page, \adminModule\AdminPresenter $presenter, string $name): self {
        $bar = new AdminAdminBarControl($presenter, $name);
        $bar->setPageWrapper($page);
        return $bar;
    }

    public function render() {
        $this->template->page = $this->pageWrapper;
        $this->template->username = $this->getPresenter()->getUser()->getIdentity()->getUsername();
        $this->template->render();
    }

    private function setPageWrapper(PageWrapper $pageWrapper) {
        $this->pageWrapper = $pageWrapper;
    }
}