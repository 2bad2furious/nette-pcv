<?php


class UserAuthorizator implements \Nette\Security\IAuthorizator {

    /**
     * Performs a role-based authorization.
     * @param  string|null
     * @param  string|null
     * @param  string|null
     * @return bool
     */
    function isAllowed($role, $resource, $privilege) {
        $allowedRoles = [];

        switch ($resource) {
            case PageManager::ACTION_SEE_NON_PUBLIC_PAGES:
                $allowedRoles = UserManager::ROLES_PAGE_DRAFTING;
                break;
            case PageManager::ACTION_DRAFT:
                $allowedRoles = UserManager::ROLES_PAGE_DRAFTING;
                break;
            case PageManager::ACTION_CACHE:
                $allowedRoles = UserManager::ROLES_USER_ADMINISTRATION;
                break;
            case PageManager::ACTION_MANAGE:
                $allowedRoles = UserManager::ROLES_PAGE_MANAGING;
                break;
            case SettingsManager::ACTION_MANAGE_SETTINGS:
                $allowedRoles = UserManager::ROLES_USER_ADMINISTRATION;
                break;
            case LanguageManagerOld::ACTION_CACHE:
                $allowedRoles = UserManager::ROLES_USER_ADMINISTRATION;
                break;
            case LanguageManagerOld::ACTION_MANAGE:
                $allowedRoles = UserManager::ROLES_PAGE_MANAGING;
                break;
        }

        $hasRight = in_array($role, $allowedRoles);
        return $hasRight;
    }
}