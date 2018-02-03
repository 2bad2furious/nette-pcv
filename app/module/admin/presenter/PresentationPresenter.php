<?php


namespace adminModule;


class PresentationPresenter extends AdminPresenter {

    protected function getAllowedRoles(): array {
        switch ($this->getAction()) {
            case "default":
                return \UserManager::ROLES_PAGE_MANAGING;
            case "delete":
                return \UserManager::ROLES_PAGE_MANAGING;
            case "edit":
                return \UserManager::ROLES_PAGE_MANAGING;
            case "add":
                return \UserManager::ROLES_PAGE_MANAGING;
        }
    }


}