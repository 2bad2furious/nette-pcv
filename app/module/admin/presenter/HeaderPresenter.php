<?php


namespace adminModule;


class HeaderPresenter extends AdminPresenter {

    protected function getAllowedRoles(): array {
        return \UserManager::ROLES_PAGE_MANAGING;
    }
}