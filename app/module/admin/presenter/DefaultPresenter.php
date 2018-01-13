<?php

namespace adminModule;


use BasePresenter;

class DefaultPresenter extends BasePresenter {

    public function actionDefault() {
        /** @var \UserIdentity|null $identity */
        $identity = $this->getUser()->getIdentity();

        $lang = $this->getLocaleLanguage()->getCode();

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