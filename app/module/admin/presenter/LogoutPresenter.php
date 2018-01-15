<?php


namespace adminModule;


class LogoutPresenter extends AdminPresenter {

    protected function setPageTitle(): string {

    }

    protected function setPageSubtitle(): string {
        // TODO: Implement setPageSubtitle() method.
    }

    public function actionDefault() {
        $this->getUser()->logout(true);
        $this->flashMessage("admin.logout.success");
        $this->redirect(302,"Default:");
    }

    protected function getAllowedRoles(): array {
        return \UserManager::ROLES_ADMINISTRATION;
    }

}