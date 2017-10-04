<?php


namespace adminModule;


class HomePresenter extends AdminPresenter {

    protected function getAllowedRoles(): array {
        return \UserManager::ROLES_ADMINISTRATION;
    }

    protected function setPageTitle(): string {
        return "admin.page.title.home";
    }
}