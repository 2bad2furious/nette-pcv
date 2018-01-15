<?php

interface IUserManager {
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