<?php

namespace frontModule;

class NoLangPresenter extends \BasePresenter {
    public function actionDefault() {
        $language = $this->getLocaleLanguage();
        if (!$language instanceof \Language) $language = $this->getLanguageManager()->getDefaultLanguage();

        $this->redirect(301, "Page:", ["locale" => $language->getCode()]);
    }

    protected function getAllowedRoles(): array {
        return \IUserManager::ROLES;
    }
}