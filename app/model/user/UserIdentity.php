<?php


class UserIdentity implements \Nette\Security\IIdentity {
    private $id;
    private $email;
    private $role;

    /**
     * UserIdentity constructor.
     * @param int $id
     * @param string $email
     * @param Language $currentLanguage
     * @param int $role
     */
    public function __construct(int $id, string $email, int $role) {
        $this->id = $id;
        $this->email = $email;
        $this->role = $role;
    }

    /**
     * Returns the ID of user.
     * @return int
     */
    function getId(): int {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getEmail(): string {
        return $this->email;
    }

    /**
     * Returns a list of roles that the user is a member of.
     * @return array
     */
    function getRoles(): array {
        return [$this->getRole()];
    }

    function getRole(): int {
        return $this->role;
    }
}