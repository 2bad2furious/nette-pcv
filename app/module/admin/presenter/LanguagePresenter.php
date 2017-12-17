<?php


namespace adminModule;


use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

class LanguagePresenter extends AdminPresenter {

    const ID_KEY = "id";

    /** @var int $id @persistent */
    public $id;

    protected function getAllowedRoles(): array {
        switch ($this->getAction()) {
            case "default":
                return \UserManager::ROLES_PAGE_DRAFTING;
            case "create":
                return \UserManager::ROLES_ADMIN_ADMINISTRATION;
            case "delete":
                return \UserManager::ROLES_ADMIN_ADMINISTRATION;
            case "edit":
                return \UserManager::ROLES_ADMIN_ADMINISTRATION;
        }
    }

    public function actionDefault() {
        $this->template->languages = $this->getLanguageManager()->getAvailableLanguages(true, false);
    }

    public function actionEdit() {
        $language = $this->getLanguageManager()->getById($this->getParameter(self::ID_KEY));
        if(!$language instanceof \Language){
            $this->addWarning("admin.language.edit.not_exist");
            $this->redirect(302,"Language:default",[self::ID_KEY=>null]);
        }
        $this->template->id = $language->getId();
    }

    public function actionCreate() {
        $language = $this->getLanguageManager()->createNew();
        $this->redirect(302, "edit", [self::ID_KEY => $language->getId()]);
    }

    public function createComponentLanguageEditForm() {
        $language = $this->getLanguageManager()->getById($this->getParameter(self::ID_KEY));
        $form = $this->getFormFactory()->createLanguageEditForm($language);
        $form->onSuccess[] = function (Form $form, array $values) {

            diedump(func_get_args());
        };
        return $form;
    }
}