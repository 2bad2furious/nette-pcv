<?php

interface IUserManager {
    const
        //non-logged users
        ROLE_GUEST = 0,

        //normal users
        ROLE_USER = 1,

        //users, who can only create drafts, that have to be published by someone higher
        ROLE_DRAFTER = 2, //

        //publishes drafts or creates/edits/deletes any post
        ROLE_PUBLISHER = 3,

        //manages lower people
        ROLE_ADMIN = 4,

        //manages lower people
        ROLE_SUPER_ADMIN = 5,

        //all roles
        ROLES = [self::ROLE_GUEST, self::ROLE_USER, self::ROLE_DRAFTER, self::ROLE_PUBLISHER, self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN],

        //users allowed to log in to administration
        ROLES_ADMINISTRATION = [self::ROLE_DRAFTER, self::ROLE_PUBLISHER, self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN],

        ROLES_PAGE_DRAFTING = self::ROLES_ADMINISTRATION,

        ROLES_PAGE_MANAGING = [self::ROLE_PUBLISHER, self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN],

        ROLES_USER_ADMINISTRATION = [self::ROLE_ADMIN, self::ROLE_SUPER_ADMIN],

        ROLES_ADMIN_ADMINISTRATION = [self::ROLE_SUPER_ADMIN];

    /**
     * Performs an authentication against e.g. database.
     * and returns IIdentity on success or throws AuthenticationException
     * @param array $credentials
     * @return \Nette\Security\IIdentity
     * @throws \Nette\Security\AuthenticationException
     */
    public function authenticate(array $credentials);

    /**
     * @param string $username
     * @param string|null $password
     * @return bool true on success
     * @throws Exception on failure
     */
    public function register(string $username, string $password = null): bool;

    public function resetPassword(string $username);

    public function changePassword(string $password): UserIdentity;

    public function loginCheck(string $identification, string $password): bool;

    public function getUserIdentityById(int $id): ?UserIdentity;

    public function cleanCache();

    public function saveCurrentLanguage(int $userId,string $language);
}