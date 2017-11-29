<?php


namespace adminModule;


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
        if (!\LanguageManager::isCodeGenerated($language->getCode())) {
            $this->addWarning("admin.language.edit.code_not_generated");
            $this->redirect(302, "default");
        }
    }

    public function actionCreate() {
        $language = $this->getLanguageManager()->createNew();
        $this->redirect(302, "edit", [self::ID_KEY => $language->getId()]);
    }

    public function createComponentLanguageEditForm() {
        $language = $this->getLanguageManager()->getById($this->getParameter(self::ID_KEY));
        $form = $this->getFormFactory()->createLanguageEditForm($language);
        return $form;
    }
}