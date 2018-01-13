<?php

namespace adminModule;


use BasePresenter;

class DefaultPresenter extends AdminPresenter {

    public function actionDefault() {
        $lang = $this->getAdminLocale();

        if ($this->getUser()->isLoggedIn()) {
            $this->redirect(302, "Home:Default", ["locale" => $lang]);
        } else {
            $this->redirect(302, "Login:Default", ["locale" => $lang]);
        }
    }

    protected function getAllowedRoles(): array {
        return \UserManager::ROLES;
    }
}