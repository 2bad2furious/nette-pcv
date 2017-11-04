<?php

namespace frontModule;

class NoLangPresenter extends \BasePresenter {
    public function actionDefault() {
        $language = $this->getLocaleLanguage();
        if (!$language instanceof \Language) $language = $this->getLanguageManager()->getDefaultLanguage();

        $this->redirect(301, "Page:Default", ["locale" => $language->getCode()]);
        $this->terminate();
    }

    protected function getAllowedRoles(): array {
        return \UserManager::ROLES;
    }
}