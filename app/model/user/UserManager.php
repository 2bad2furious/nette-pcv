<?php


use Nette\Database\Table\ActiveRow;
use Nette\Security\Passwords;

class UserManager implements \Nette\Security\IAuthenticator {

    const
        TABLE = "user",
        COLUMN_ID = "user_id",
        COLUMN_USERNAME = "username", COLUMN_USERNAME_LENGTH = 40, COLUMN_USERNAME_CHARSET = "",
        COLUMN_EMAIL = "email", COLUMN_EMAIL_LENGTH = 100,
        COLUMN_PASSWORD = "password", COLUMN_PASSWORD_LENGTH = 255,
        COLUMN_CREATED = "created",
        COLUMN_VERIFIED = "verified",
        COLUMN_ROLE = "role",
        COLUMN_CURRENT_LANGUAGE = "cur_lang",

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

    /** @var \Nette\Security\User */
    private $user;

    /** @var  \Nette\Database\Context */
    private $database;
    /**
     * @var LanguageManager
     */
    private $languageManager;
    /**
     * @var \Nette\DI\Container
     */
    private $context;

    /**
     * UserManager constructor.
     * @param \Nette\Database\Context $database
     * @param \Nette\DI\Container $context
     */
    public function __construct(\Nette\Database\Context $database, \Nette\DI\Container $context) {
        $this->database = $database;
        $this->context = $context;
    }

    /* because Nette DI sucks ass :/ */
    private function getLanguageManger(): LanguageManager {
        return $this->context->getByType(LanguageManager::class);
    }

    private function getUser(): \Nette\Security\User {
        return $this->context->getByType(\Nette\Security\User::class);
    }

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

    public function getUserIdentityById(?int $id):?UserIdentity {
        if ($id === null) return null;

        $data = $this->database->table(self::TABLE)->get($id);

        if ($data instanceof ActiveRow) {
            return $this->createFromDbRow($data);
        }
        return null;
    }

    private function getUserIdentityByIdentificationPassword(string $identification, string $password):?UserIdentity {
        $data = $this->database->table(self::TABLE)->whereOr([
            self::COLUMN_USERNAME => $identification,
            self::COLUMN_EMAIL    => $identification,
        ])->fetch();

        if ($data instanceof ActiveRow && Passwords::verify($password, $data[self::COLUMN_PASSWORD])) {
            return $this->createFromDbRow($data);
        }
        return null;
    }

    private function createFromDbRow(ActiveRow $data): UserIdentity {
        return new UserIdentity($data[self::COLUMN_ID], $data[self::COLUMN_EMAIL], $this->getLanguageManger()->getById($data[self::COLUMN_CURRENT_LANGUAGE]), $data[self::COLUMN_ROLE]);
    }
}