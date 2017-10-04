<?php


namespace adminModule;


use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

class LoginPresenter extends AdminPresenter {
    const USER_NO_RIGHTS_ERROR = "forms.login.error.rights";

    protected function createComponentLoginForm() {
        $form = $this->getFormFactory()->createLoginForm();

        $form->onValidate[] = function (Form $form, ArrayHash $values) {
            $username = $values[\FormFactory::LOGIN_IDENTIFICATION_NAME];
            $password = $values[\FormFactory::LOGIN_PASSWORD_NAME];

            if (!$this->getUserManager()->loginCheck($username, $password)) {
                $form->addError(\FormFactory::LOGIN_INVALID_CREDENTIALS);
            }
        };
        $form->onSuccess[] = function (Form $form) {
            try {
                $values = $form->getValues();
                $identification = $values[\FormFactory::LOGIN_IDENTIFICATION_NAME];
                $password = $values[\FormFactory::LOGIN_PASSWORD_NAME];
                $this->getUser()->login($identification, $password);
                if ($this->getUser()->getIdentity()->getRole() === 0) {
                    $this->getUser()->logout(true);
                    $form->addError(self::USER_NO_RIGHTS_ERROR);
                }
                $this->redirect(302, "Default:default");
            } catch (\Exception $ex) {
                $this->getUser()->logout(true);
                $form->addError(self::SOMETHING_WENT_WRONG);
                throw $ex;
            }
        };

        return $form;
    }

    public function setAdminLanguage() {
        $this->locale = $this->translator->getLocale();
    }

    protected function getAllowedRoles(): array {
        return [\UserManager::ROLE_GUEST];
    }

    protected function setPageTitle(): string {
        return "admin.page.login.title";
    }
}