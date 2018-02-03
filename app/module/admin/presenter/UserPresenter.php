<?php


namespace adminModule;


class UserPresenter extends AdminPresenter {

    protected function getAllowedRoles(): array {
        switch ($this->getAction()) {
            case "default":
                return \IUserManager::ROLES_ADMINISTRATION;
            case "edit":
                return \IUserManager::ROLES_ADMIN_ADMINISTRATION;
            case "delete":
                return \IUserManager::ROLES_ADMIN_ADMINISTRATION;
            case "add":
                return \IUserManager::ROLES_ADMIN_ADMINISTRATION;
        }
    }
}