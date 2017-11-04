<?php


namespace frontModule;


use Exception;
use Nette\Application\UI\Presenter;

class TestPresenter extends Presenter {


    public function actionDefault() {
        /** @var \LanguageManager $languageManager */
        $languageManager = $this->context->getByType(\LanguageManager::class);
        $languageManager->rebuildCache();
    }

    protected function getAllowedRoles(): array {
        return \UserManager::ROLES;
    }
}