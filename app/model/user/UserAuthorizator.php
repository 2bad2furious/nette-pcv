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
        }

        $hasRight = in_array($role, $allowedRoles);
        return $hasRight;
    }
}