<?php


namespace adminModule;


class HomePresenter extends AdminPresenter {

    protected function getAllowedRoles(): array {
        return \UserManager::ROLES_ADMINISTRATION;
    }

    public function actionDefault() {
        $this->redirect(301, "Page:");
    }

    protected function setPageTitle(): string {
        return "";
    }

    protected function setPageSubtitle(): string {
        return "";
    }
}