<?php


namespace adminModule;


use Nette\Application\UI\Form;
use Nette\Neon\Exception;
use Nette\Utils\ArrayHash;
use Tracy\Debugger;

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
        if (!$language instanceof \Language) {
            $this->addWarning("admin.language.edit.not_exist");
            $this->redirect(302, "Language:default", [self::ID_KEY => null]);
        }
        $this->template->id = $language->getId();
    }

    public function actionCreate() {
        $language = $this->getLanguageManager()->createNew();
        $this->redirect(302, "edit", [self::ID_KEY => $language->getId()]);
    }

    public function actionDelete(){
        $id = (int)$this->getParameter(self::ID_KEY);
        $this->getLanguageManager()->delete($id);
    }

    public function createComponentLanguageEditForm() {
        $language = $this->getLanguageManager()->getById($this->getParameter(self::ID_KEY));
        $form = $this->getFormFactory()->createLanguageEditForm($language);
        $form->onSuccess[] = function (Form $form, array $values) use ($language) {
            try {
                $this->getLanguageManager()->edit(
                    $language,
                    $values[\FormFactory::LANGUAGE_EDIT_CODE_NAME],
                    $values[\FormFactory::LANGUAGE_EDIT_GOOGLE_ANALYTICS_NAME],
                    $values[\FormFactory::LANGUAGE_EDIT_SITE_TITLE_NAME],
                    $values[\FormFactory::LANGUAGE_EDIT_TITLE_SEPARATOR_NAME],
                    $values[\FormFactory::LANGUAGE_EDIT_LOGO_NAME],
                    $values[\FormFactory::LANGUAGE_EDIT_HOMEPAGE],
                    $values[\FormFactory::LANGUAGE_EDIT_FAVICON_NAME]
                );
                $this->redirect(302,":default",[self::ID_KEY=>null]);
            } catch (Exception $e) {
                Debugger::log($e);
                $this->somethingWentWrong();
                throw $e;
                $this->redirect(302,"this");
            }
        };
        return $form;
    }
}