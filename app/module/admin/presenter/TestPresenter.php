<?php


namespace adminModule;


use Nette\Database\Context;

class TestPresenter extends AdminPresenter {

    protected function getAllowedRoles(): array {
        return \UserManager::ROLES;
    }

    public function actionDefault() {
        /** @var \PageManager $pageManager */
        $pageManager = $this->context->getByType(\PageManager::class);
        $pageManager->rebuildCache();
    }
}