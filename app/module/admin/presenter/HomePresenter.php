<?php


namespace adminModule;


class HomePresenter extends AdminPresenter {

    protected function getAllowedRoles(): array {
        return \IUserManager::ROLES_ADMINISTRATION;
    }
}