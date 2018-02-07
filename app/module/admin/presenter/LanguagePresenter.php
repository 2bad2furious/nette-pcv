<?php


namespace adminModule;


use Language;
use Nette\Application\UI\Form;
use PaginatorControl;

class LanguagePresenter extends AdminPresenter {

    const ID_KEY = "id",
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

    /**
     * @return array
     */
    protected function getAllowedRoles(): array {
        switch ($this->getAction()) {
            case "default":
                return \AccountManager::ROLES_PAGE_DRAFTING;
            case "add":
                return \AccountManager::ROLES_ADMIN_ADMINISTRATION;
            case "edit":
                return \AccountManager::ROLES_ADMIN_ADMINISTRATION;
        }
    }

    /**
     * @throws \LanguageByIdNotFound
     */
    public function actionDefault() {
        $this->template->languages = $this->getLanguageManager()->getFiltered(
            $this->getCurrentPage(),
            10,
            $this->numOfPages,
            $this->getSearched());

        $this->checkPaging(
            $this->getCurrentPage(),
            $this->numOfPages,
            self::PAGE_KEY);


        try {
            if (!$this->isComingFromThis()) $this->redrawContent();
            else $this->redrawLanguages();
        } catch (\InvalidState $e) {
            $this->redrawContent();
        }
    }


    public function createComponentPaginator(string $name) {
        return new PaginatorControl($this, $name, self::PAGE_KEY, $this->getCurrentPage(), $this->numOfPages);
    }

    /**
     * @throws \LanguageByIdNotFound
     * @throws \Nette\Application\AbortException
     */
    public function actionEdit() {
        $language = $this->getLanguageManager()->getById($this->getParameter(self::ID_KEY), false);
        if (!$language instanceof \Language) {
            $this->addWarning("admin.language.edit.not_exist");
            $this->postGet("this");
        }
        $this->template->id = $language->getId();
    }

    /**
     * @param int $id
     * @throws \CannotDeleteLastLanguage
     * @throws \LanguageByIdNotFound
     */
    public function handleDelete(int $id) {
        if (!($language = $this->getLanguageManager()->getById($id, false)) instanceof Language)
            $this->addWarning("admin.language.delete.not_exist");
        else if ($this->getLanguageManager()->getDefaultLanguage()->getId() == $language->getId()) {
            $this->addWarning("admin.language.delete.default");
        } else if (count($this->getLanguageManager()->getAvailableLanguages()) == 1) {
            $this->addWarning("admin.language.delete.last");
        } else {
            $this->getLanguageManager()->delete($id);
            $this->addSuccess("admin.language.delete.success");
        }
        $this->redirect(302, "default", ["id" => null]);
    }

    /**
     * @return Form
     * @throws \LanguageByIdNotFound
     */
    public function createComponentLanguageEditForm() {
        $language = $this->getLanguageManager()->getById($this->getParameter(self::ID_KEY));
        $form = $this->getFormFactory()->createLanguageEditForm($language);
        //$form->setAction($this->link("this", [self::ID_KEY => $language->getId()]));
        $form->onSuccess[] = function (Form $form, array $values) use ($language) {
            $this->commonTryCall(function () use ($values, $language) {
                $this->getLanguageManager()->edit(
                    $language->getId(),
                    $values[\FormFactory::LANGUAGE_EDIT_FRIENDLY_NAME],
                    $values[\FormFactory::LANGUAGE_EDIT_GOOGLE_ANALYTICS_NAME],
                    $values[\FormFactory::LANGUAGE_EDIT_SITE_TITLE_NAME],
                    $values[\FormFactory::LANGUAGE_EDIT_TITLE_SEPARATOR_NAME],
                    $values[\FormFactory::LANGUAGE_EDIT_LOGO_NAME],
                    $values[\FormFactory::LANGUAGE_EDIT_HOMEPAGE],
                    $values[\FormFactory::LANGUAGE_EDIT_FAVICON_NAME],
                    $values[\FormFactory::LANGUAGE_EDIT_404]
                );
            });
            $this->postGet("this");
        };
        return $form;
    }

    public function createComponentLanguageAddForm(): Form {
        $form = $this->getFormFactory()->createLanguageAddForm();

        $form->onSuccess[] = function (Form $form, array $values) {
            /** @var Language $language */
            $language = $this->commonTryCall(function () use ($values) {
                return $this->getLanguageManager()->add(
                    $values[\FormFactory::LANGUAGE_EDIT_CODE_NAME],
                    $values[\FormFactory::LANGUAGE_EDIT_SITE_TITLE_NAME],
                    $values[\FormFactory::LANGUAGE_EDIT_FRIENDLY_NAME]
                );
            });
            $this->redirect(302, "edit", [self::ID_KEY => $language->getId()]);
        };
        return $form;
    }

    private function getCurrentPage(): int {
        $page = (int)$this->getParameter(self::PAGE_KEY);
        if ($page === 0) $page = 1;
        return $page;
    }

    private function getSearched(): ?string {
        return $this->getParameter(self::SEARCH_KEY);
    }

    public function createComponentLanguageSearchForm() {
        $form = $this->getFormFactory()->createLanguageSearchForm($this->getSearched());
        $form->onSubmit[] = function () {
            $this->redirect(302, "this", [self::SEARCH_KEY => $this->getSearched()]);
        };
        return $form;
    }

    private function redrawLanguages() {
        $this->redrawControl("languages");
    }
}