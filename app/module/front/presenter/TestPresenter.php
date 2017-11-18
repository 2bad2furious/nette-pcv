<?php


namespace frontModule;


use Exception;
use Nette\Application\UI\Presenter;

class TestPresenter extends Presenter {


    public function actionDefault() {
        $this->sendJson("nothing");
    }

    protected function getAllowedRoles(): array {
        return \UserManager::ROLES;
    }
}