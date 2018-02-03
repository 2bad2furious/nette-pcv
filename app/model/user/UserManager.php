<?php


use Nette\Database\Context;
use Nette\Database\IRow;
use Nette\Database\Table\ActiveRow;
use Nette\Security\Passwords;
use Nette\Security\User;

class UserManager extends Manager implements \Nette\Security\IAuthenticator, IUserManager {

    const
        TABLE = "user",
        COLUMN_ID = "user_id",
        COLUMN_USERNAME = "username", COLUMN_USERNAME_LENGTH = 40, COLUMN_USERNAME_CHARSET = "",
        COLUMN_EMAIL = "email", COLUMN_EMAIL_LENGTH = 100,
        COLUMN_PASSWORD = "password", COLUMN_PASSWORD_LENGTH = 255,
        COLUMN_CREATED = "created",
        COLUMN_VERIFIED = "verified",
        COLUMN_ROLE = "role",
        COLUMN_CURRENT_LANGUAGE = "current_language", COLUMN_CURRENT_LANGUAGE_LENGTH = 5;


    /**
     * Performs an authentication against e.g. database.
     * and returns IIdentity on success or throws AuthenticationException
     * @param array $credentials
     * @return \Nette\Security\IIdentity
     * @throws \Nette\Security\AuthenticationException
     */
    public function authenticate(array $credentials) {
        $identity = $this->getUserIdentityByIdentificationPassword($credentials[0], $credentials[1]);

        if (!$identity instanceof UserIdentity) throw new \Nette\Security\AuthenticationException();

        return $identity;
    }

    /**
     * @param string $username
     * @param string|null $password
     * @return bool true on success
     * @throws Exception on failure
     */
    public function register(string $username, string $password = null): bool {

    }

    public function resetPassword(string $username) {

    }

    public function changePassword(string $password): UserIdentity {

    }


    public function loginCheck(string $identification, string $password): bool {
        return $this->getUserIdentityByIdentificationPassword($identification, $password) instanceof UserIdentity;
    }

    public function getUserIdentityById(int $id): ?UserIdentity {
        return $this->get($id);
    }

    private function getUserIdentityByIdentificationPassword(string $identification, string $password): ?UserIdentity {
        $data = $this->getDatabase()->table(self::TABLE)->whereOr([
            self::COLUMN_USERNAME => $identification,
            self::COLUMN_EMAIL => $identification,
        ])->select(self::COLUMN_PASSWORD)->select(self::COLUMN_ID)->fetch();
        if ($data instanceof ActiveRow && Passwords::verify($password, $data[self::COLUMN_PASSWORD])) {
            $identity = $this->get($data[self::COLUMN_ID]);

            return $identity;
        }
        return null;
    }

    private function createFromDbRow(ActiveRow $data): UserIdentity {
        return new UserIdentity(
            $data[self::COLUMN_ID],
            $data[self::COLUMN_USERNAME],
            $data[self::COLUMN_EMAIL],
            $data[self::COLUMN_CURRENT_LANGUAGE],
            $data[self::COLUMN_ROLE]
        );
    }

    private function get(int $id): ?UserIdentity {
        return $this->getCache()->load($id, function () use ($id) {
            $user = $this->getDatabase()->table(self::TABLE)->where([self::COLUMN_ID => $id])->fetch();

            return $user instanceof IRow ? $this->createFromDbRow($user) : null;
        });
    }

    public function cleanCache() {
        $this->getCache()->clean([]);
    }

    private function getCache(): Cache {
        static $cache = null;
        return $cache instanceof Cache ? $cache : $cache = new Cache($this->getDefaultStorage(), "user");
    }

    public function saveCurrentLanguage(int $userId, string $language): UserIdentity {
        if (mb_strlen($language) > self::COLUMN_CURRENT_LANGUAGE_LENGTH) throw new InvalidArgumentException("Language must be at most " . self::COLUMN_CURRENT_LANGUAGE_LENGTH . " characters long. " . mb_strlen($language));

        $this->uncache($userId);

        $changed = $this->runInTransaction(function () use ($userId, $language) {
            return $this->getDatabase()->table(self::TABLE)
                ->wherePrimary($userId)
                ->update([
                    self::COLUMN_CURRENT_LANGUAGE => $language
                ]);
        });//TODO check whether something was changed? throw exception?

        return $this->getUserIdentityById($userId);
    }

    private function uncache(int $userId) {
        $this->getCache()->remove($userId);
    }
}