<?php

use Nette\Application\UI\Form;

class FormFactory {

    const LOGIN_IDENTIFICATION_NAME = "login_identification",
        LOGIN_IDENTIFICATION_LABEL = "form.login.identification.label",
        LOGIN_PASSWORD_NAME = "login_password",
        LOGIN_PASSWORD_LABEL = "form.login.password.label",
        LOGIN_SUBMIT_LABEL = "form.login.submit.label",
        LOGIN_SUBMIT_NAME = "login_submit",
        LOGIN_INVALID_CREDENTIALS = "form.login.error.password";
    /** @var \Kdyby\Translation\Translator */
    private $translator;

    /**
     * FormFactory constructor.
     * @param \Kdyby\Translation\Translator $translator
     */
    public function __construct(\Kdyby\Translation\Translator $translator) {
        $this->translator = $translator;
    }


    private function createNewForm(): Form {
        $form = new Form();
        $form->setTranslator($this->translator);
        return $form;
    }

    private function createNewAdminForm(): Form {
        $form = $this->createNewForm();
        return $form;
    }

    public function createLoginForm(): Form {
        $form = $this->createNewForm();
        $form->addText(self::LOGIN_IDENTIFICATION_NAME, self::LOGIN_IDENTIFICATION_LABEL);
        $form->addPassword(self::LOGIN_PASSWORD_NAME, self::LOGIN_PASSWORD_LABEL);
        $form->addSubmit(self::LOGIN_SUBMIT_NAME, self::LOGIN_SUBMIT_LABEL);
        return $form;
    }
}