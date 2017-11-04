<?php

namespace adminModule;


use BasePresenter;

class DefaultPresenter extends BasePresenter {

    public function actionDefault() {
        /** @var \UserIdentity|null $identity */
        $identity = $this->getUser()->getIdentity();

        $lang = $this->getLocaleLanguage() instanceof \Language ? $this->getLocaleLanguage()->getCode() : (
        $identity instanceof \UserIdentity ?
            $identity->getCurrentLanguage()->getCode() :
            $this->getLanguageManager()->getDefaultLanguage()->getCode()
        );

        if ($this->getUser()->isLoggedIn()) {
            $this->redirect(302, "Home:Default", ["locale" => $lang]);
        } else {
            $this->redirect(302, "Login:Default", ["locale" => $lang]);
        }
    }

    protected function getAllowedRoles(): array {
        return \UserManager::ROLES;
    }

    protected function setPageTitle(): string {
        return "";
    }
}