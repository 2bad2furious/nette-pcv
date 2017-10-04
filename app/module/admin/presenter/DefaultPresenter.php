<?php

namespace adminModule;


use BasePresenter;

class DefaultPresenter extends BasePresenter {

    public function actionDefault() {
        if($this->getUser()->isLoggedIn()){
            $this->redirect(302,"Home:Default");
        }else{
            $this->redirect(302,"Login:Default");
        }
    }

    protected function getAllowedRoles(): array {
        return \UserManager::ROLES;
    }

    protected function setPageTitle(): string {
        return "";
    }
}