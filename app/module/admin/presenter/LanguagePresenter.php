<?php


namespace adminModule;


use Language;
use LanguageManagerOld;
use Nette\Application\UI\Form;
use Nette\Neon\Exception;
use Nette\Utils\ArrayHash;
use PaginatorControl;
use Tracy\Debugger;

class LanguagePresenter extends AdminPresenter {

    const ID_KEY = "id",
        GENERATED_KEY = "generated",
        SEARCH_KEY = "search",
        PAGE_KEY = "page";

    private $numOfPages = 0;
    /** @persistent */
    public $generated;
    /** @persistent */
    public $search;
    /** @persistent */
    public $page;
    /** @persistent */
    public $id;

    protected function getAllowedRoles(): array {
        switch ($this->getAction()) {
            case "show":
                return \UserManager::ROLES_PAGE_DRAFTING;
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
        $this->template->isGenerated = $this->isGenerated();
        $this->template->languages = $this->getLanguageManager()->getFiltered($this->getCurrentPage(), 10, $this->numOfPages, $this->getSearched(), $this->isGenerated());
        $this->checkPaging($this->getCurrentPage(), $this->numOfPages, self::PAGE_KEY);
    }


    public function createComponentPaginator(string $name) {
        return new PaginatorControl($this, $name, self::PAGE_KEY, $this->getCurrentPage(), $this->numOfPages);
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

    public function actionDelete() {
        $id = (int)$this->getParameter(self::ID_KEY);
        if (!$this->getLanguageManager()->getById($id) instanceof Language)
            $this->addWarning("admin.language.delete.not_exist");
        else
            $this->getLanguageManager()->delete($id);


        $this->redirect(302, ":default", [self::ID_KEY => null]);
    }

    public function createComponentLanguageEditForm() {
        $language = $this->getLanguageManager()->getById($this->getParameter(self::ID_KEY));
        $form = $this->getFormFactory()->createLanguageEditForm($language);
        //$form->setAction($this->link("this", [self::ID_KEY => $language->getId()]));
        $form->onSuccess[] = function (Form $form, array $values) use ($language) {
            try {
                $this->getLanguageManager()->edit(
                    $language,
                    $values[\FormFactory::LANGUAGE_EDIT_CODE_NAME],
                    $values[\FormFactory::LANGUAGE_EDIT_GOOGLE_ANALYTICS_NAME],
                    $values[\FormFactory::LANGUAGE_EDIT_SITE_TITLE_NAME],
                    $values[\FormFactory::LANGUAGE_EDIT_TITLE_SEPARATOR_NAME],
                    $values[\FormFactory::LANGUAGE_EDIT_LOGO_NAME],
                    (int)$values[\FormFactory::LANGUAGE_EDIT_HOMEPAGE],
                    $values[\FormFactory::LANGUAGE_EDIT_FAVICON_NAME]
                );
                $this->redirect(302, ":default", [self::ID_KEY => null]);
            } catch (Exception $e) {
                Debugger::log($e);
                $this->somethingWentWrong();
                throw $e;
                $this->redirect(302, "this");
            }
        };
        return $form;
    }

    private function getCurrentPage(): int {
        $page = (int)$this->getParameter(self::PAGE_KEY);
        if ($page === 0) $page = 1;
        return $page;
    }

    private function getSearched():?string {
        return $this->getParameter(self::SEARCH_KEY);
    }

    private function isGenerated():?bool {
        return $this->getParameter(self::GENERATED_KEY);
    }

    public function createComponentLanguageSearchForm(string $name) {
        $form = $this->getFormFactory()->createLanguageSearchForm($this->getSearched());
        $form->onSubmit[] = function () {
            $this->redirect(302, "this", [self::SEARCH_KEY => $this->getSearched()]);
        };
        return $form;
    }
}