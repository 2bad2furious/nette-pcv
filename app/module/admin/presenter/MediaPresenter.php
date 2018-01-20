<?php


namespace adminModule;


class MediaPresenter extends AdminPresenter {

    protected function getAllowedRoles(): array {
        switch ($this->getAction()) {
            case "default":
                return \UserManager::ROLES_ADMINISTRATION;
        }
    }

    public function renderDefault(){

    }
}