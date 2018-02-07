<?php


namespace adminModule;


class AccountsPresenter extends AdminPresenter {

    const ROLE_KEY = "role",
        PAGE_KEY = "page";

    private $numOfPages;

    protected function getAllowedRoles(): array {
        switch ($this->getAction()) {
            case "default":
                return \IAccountManager::ROLES_ADMIN_ADMINISTRATION;
            case "edit":
                return \IAccountManager::ROLES_ADMIN_ADMINISTRATION;
            case "delete":
                return \IAccountManager::ROLES_ADMIN_ADMINISTRATION;
            case "add":
                return \IAccountManager::ROLES_ADMIN_ADMINISTRATION;
        }
    }

    public function renderDefault() {
        dump($this->template->getParameters());
        $this->template->accounts = $this->getAccountManager()->getAll($this->getRoles(), $this->getCurrentPage(), 5, $this->numOfPages);

        $this->checkPaging($this->getCurrentPage(), $this->numOfPages, self::PAGE_KEY);
    }

    public function getWantedRole(): ?int {
        return $this->getParameter(self::ROLE_KEY);
    }


    public function getRoles(): ?array {
        $wanted = $this->getWantedRole();
        if (!is_int($wanted)) return null;
        return [$wanted];//TODO allow more roles?
    }

    public function getCurrentPage(): int {
        return $this->getParameter(self::PAGE_KEY, 1);
    }

    public function createComponentPaginator(string $name): \PaginatorControl {
        return new \PaginatorControl($this, $name, self::PAGE_KEY, $this->getCurrentPage(), $this->numOfPages);
    }
}