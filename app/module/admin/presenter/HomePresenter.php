<?php


namespace adminModule;


class HomePresenter extends AdminPresenter {

    protected function getAllowedRoles(): array {
        return \IAccountManager::ROLES_ADMINISTRATION;
    }
}