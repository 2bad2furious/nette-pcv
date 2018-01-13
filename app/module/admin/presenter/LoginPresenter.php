<?php


namespace adminModule;


use Nette\Application\UI\Form;
use Nette\Http\UrlScript;
use Nette\Utils\ArrayHash;

class LoginPresenter extends AdminPresenter {
    const USER_NO_RIGHTS_ERROR = "forms.login.error.rights";

    protected function createComponentLoginForm() {
        $form = $this->getFormFactory()->createLoginForm();

        $form->onValidate[] = function (Form $form, ArrayHash $values) {
            $username = $values[\FormFactory::LOGIN_IDENTIFICATION_NAME];
            $password = $values[\FormFactory::LOGIN_PASSWORD_NAME];
            if (!$this->getUserManager()->loginCheck($username, $password)) {
                $form->addError("admin.login.failure.password");
            }
        };
        $form->onSuccess[] = function (Form $form, array $values) {
            $this->commonTryCall(function () use ($values, $form) {
                $values = $form->getValues();
                $identification = $values[\FormFactory::LOGIN_IDENTIFICATION_NAME];
                $password = $values[\FormFactory::LOGIN_PASSWORD_NAME];
                $this->getUser()->login($identification, $password);
                if ($this->getUser()->getIdentity()->getRole() === 0) {
                    $this->getUser()->logout(true);
                    $form->addError(self::USER_NO_RIGHTS_ERROR);
                }
            }, function () use ($form) {
                $this->getUser()->logout(true);
                $form->addError(self::SOMETHING_WENT_WRONG);
            });

            if (!$form->getErrors()) {
                $this->disallowAjax();
                // redirects to previous failed url or to Home
                if ($this->getCustomSession()->offsetExists("url")) {
                    /** @var UrlScript $url */
                    $url = $this->getCustomSession()->offsetGet("url");
                    $this->getCustomSession()->offsetUnset("url");
                    $this->redirectUrl($url->getPath(), 302);
                } else $this->redirect(302, "Default:default");
            }
        };

        return $form;
    }

    protected function getAllowedRoles(): array {
        return [\UserManager::ROLE_GUEST];
    }

    protected function getCallbackWhenBadRole(array $allowedRoles, int $currentRole): callable {
        $this->redirect("Default:Default");
    }

    protected function actionDefault() {
        $this->disallowAjax();
    }
}