<?php

use Nette\Security\User;
use Nette\Utils\DateTime;

class TokenManager {

    const TOKEN_TABLE = "token",
        COLUMN_TOKEN = "token",
        COLUMN_TOKEN_LENGTH = 40,
        COLUMN_ID = "token_id",
        COLUMN_EXPIRE = "expire",
        COLUMN_USED = "used",
        COLUMN_CREATED = "created",
        COLUMN_ACTION = "action";
    const CHARSET = "a-zA-Z0-9";

    /* 60*60*24 */
    const DAY = 86400;

    /* 60*60 */
    const HOUR = 3600;

    const ACTION_USER_VERIFY = "user-verify",
        ACTION_RESET_PASSWORD = "reset-password",
        ACTIONS = [self::ACTION_RESET_PASSWORD, self::ACTION_USER_VERIFY],
        ONE_USE_ACTIONS = [self::ACTION_USER_VERIFY, self::ACTION_RESET_PASSWORD];

    /**
     * @var \Nette\Database\Context $database
     */
    private $database;
    /*
     * @var Nette\Security\User $user
     */
    private $user;

    /**
     * TokenManager constructor.
     * @param \Nette\Database\Context $context
     */
    public function __construct(Nette\Database\Context $context) {
        $this->database = $context;
    }

    /**
     * @param string $action name for your action
     * @param int|null $expireTime number of seconds, null for no limit
     * @param int $user_id
     * @return string
     */
    public function createNew(string $action, int $user_id, ?int $expireTime = null): string {
        $token = \Nette\Utils\Random::generate(self::COLUMN_TOKEN_LENGTH);
        if ($this->exists($token)) {
            return $this->createNew($action, $user_id, $expireTime);
        }
        if (!is_null($expireTime))
            $expireTime = DateTime::from($expireTime);
        $this->database->table(self::TOKEN_TABLE)->insert([
            self::COLUMN_TOKEN  => $token,
            UserManager::COLUMN_ID => $user_id,
            self::COLUMN_ACTION         => $action,
            self::COLUMN_EXPIRE         => $expireTime,
        ]);
        return $token;
    }

    /**
     * @param string $token
     * @param string|null $action
     * @return bool
     */
    public function exists(string $token, ?string $action = null): bool {
        return $this->getResult($token, $action, false) instanceof StdClass;
    }

    /**
     * @param string $token
     * @param string $action
     * @param bool $check
     * @return null|StdClass
     */
    public function getByTokenStringAndAction(string $token, string $action, bool $check = true):?StdClass {
        $result = $this->getResult($token, $action, $check);
        //if exists
        if ($result instanceof StdClass) {
            $this->database->table(self::TOKEN_TABLE)->where([
                self::COLUMN_ID => $result->{self::COLUMN_ID},
            ])->update([
                self::COLUMN_USED=> $result->{self::COLUMN_USED} + 1,
            ]);
            return $result;
        }
        return null;
    }

    /**
     * searches the db
     * @param string $token
     * @param null|string $action
     * @param bool $check
     * @return bool|mixed|\Nette\Database\Table\IRow
     */
    private function getResult(string $token, ?string $action, bool $check) {
        $where = [
            self::COLUMN_TOKEN => $token,
        ];
        if ($action)
            $where[self::COLUMN_ACTION] = $action;

        if ($check) {
            if ($cur_user_id = intval($this->user->getId()))
                $where[UserManager::COLUMN_ID] = $cur_user_id;
            if (in_array($action, self::ONE_USE_ACTIONS)) {
                $where[self::COLUMN_USED] = 0;
            }
        };
        return $this->database->table(self::TOKEN_TABLE)->where($where)->fetch();
    }

    public function injectUser(User $user) {
        if($this->user instanceof User) throw new Exception("User already set");
        $this->user = $user;
    }
}