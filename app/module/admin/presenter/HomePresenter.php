<?php


namespace adminModule;


class HomePresenter extends AdminPresenter {

    protected function getAllowedRoles(): array {
        return \UserManager::ROLES_ADMINISTRATION;
    }
}