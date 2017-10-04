<?php

namespace frontModule;

class NoLangPresenter extends \BasePresenter {
    public function actionDefault() {
        $locale = $this->translator->getLocale();
        if (!$this->getLanguageManager()->exists($locale)) {
            $locale = $this->getLanguageManager()->getDefaultLanguage()->getCode();
        }
        $this->redirectUrl("/$locale/", 302);
        $this->terminate();
    }

    protected function getAllowedRoles(): array {
        return \UserManager::ROLES;
    }
}