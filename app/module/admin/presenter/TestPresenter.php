<?php


namespace adminModule;


use Nette\Database\Context;

class TestPresenter extends AdminPresenter {

    protected function getAllowedRoles(): array {
        return \UserManager::ROLES;
    }

    public function actionDefault() {
        /** @var Context $db */
        $db = $this->context->getByType(Context::class);
        $db->query("DELETE FROM ".\PageManager::MAIN_TABLE.";
DELETE FROM ".\PageManager::LOCAL_TABLE.";

ALTER TABLE ".\PageManager::MAIN_TABLE." AUTO_INCREMENT = 0;
ALTER TABLE ".\PageManager::LOCAL_TABLE." AUTO_INCREMENT = 0;");
    }
}