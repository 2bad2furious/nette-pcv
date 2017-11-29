<?php


namespace adminModule;


use Kdyby\Translation\Translator;
use Nette\Application\UI\Form;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\TextInput;
use Nette\Http\IResponse;
use Nette\Neon\Exception;
use PageManager;
use PaginatorControl;
use Tracy\Debugger;

class PagePresenter extends AdminPresenter {
    const VISIBILITY_KEY = "visibility",
        VISIBILITY_ALL = "all",
        VISIBILITY_PUBLIC = "public",
        VISIBILITY_DRAFT = "draft",
        VISIBILITY_DELETED = "deleted",
        VISIBILITIES = [self::VISIBILITY_ALL, self::VISIBILITY_PUBLIC, self::VISIBILITY_DRAFT, /*self::VISIBILITY_DELETED until figured out*/],

        DEFAULT_VISIBILITY = self::VISIBILITY_PUBLIC,

        VISIBILITY_TABLE = [
        self::VISIBILITY_ALL     => PageManager::STATUS_ALL,
        self::VISIBILITY_PUBLIC  => PageManager::STATUS_PUBLIC,
        self::VISIBILITY_DELETED => PageManager::STATUS_DELETED,
        self::VISIBILITY_DRAFT   => PageManager::STATUS_DRAFT,
    ],

        TYPE_KEY = "type",
        TYPE_ALL = "all",
        TYPE_PAGE = "page",
        TYPE_POST = "post",
        TYPES = [self::TYPE_ALL, self::TYPE_PAGE, self::TYPE_POST],

        DEFAULT_TYPE = self::TYPE_ALL,

        TYPE_TABLE = [
        self::TYPE_ALL  => PageManager::TYPE_ALL,
        self::TYPE_PAGE => PageManager::TYPE_PAGE,
        self::TYPE_POST => PageManager::TYPE_POST,
    ],
        PAGE_KEY = "page",

        LANGUAGE_KEY = "language",
        LANGUAGE_ALL = "all",

        DEFAULT_LANGUAGE = self::LANGUAGE_ALL,

        HAS_TRANSLATION_KEY = "has_translation",

        ID_KEY = "page_id";

    /** @persistent */
    public $page_id;

    /** @persistent */
    public $type;

    /** @persistent */
    public $visibility;

    /** @persistent */
    public $language;

    /** @persistent */
    public $has_translation;

    /** @persistent */
    public $search_query;

    private $numberOfPages = 0;

    protected function getAllowedRoles(): array {
        switch ($this->getAction()) {
            case "default":
                return \UserManager::ROLES_PAGE_DRAFTING;
            case "show":
                return \UserManager::ROLES_PAGE_DRAFTING;
            case "create":
                return \UserManager::ROLES_PAGE_MANAGING;
            case "edit":
                return \UserManager::ROLES_PAGE_MANAGING;
            case "delete":
                return \UserManager::ROLES_PAGE_MANAGING;
        }
    }

    public function actionCreate() {
        $globalId = $this->getPageManager()->addEmpty(self::TYPE_TABLE[$this->getParameter(self::TYPE_KEY)]);
        $args = [self::ID_KEY => $globalId, self::TYPE_KEY => null];
        if ($this->getLanguage() === null) $args[self::LANGUAGE_KEY] = $this->getLocaleLanguage()->getCode();
        $this->redirect(302, "edit", $args);
    }

    public function actionEdit() {
        $globalId = $this->getParameter(self::ID_KEY);
        $langCode = $this->getParameter(self::LANGUAGE_KEY);
        $language = $this->getLanguageManager()->getByCode($langCode);

        $page = $this->getPageManager()->getByGlobalId($language, $globalId);
        if (!$page instanceof \Page) {
            $this->addError("admin.page.edit.page_not_found");
            $this->redirect(302, "show", [self::LANGUAGE_KEY => self::LANGUAGE_ALL, self::ID_KEY => null]);
        }
        $this->template->page = $page;
    }

    public function actionShow() {
        $this->template->type = $this->getType();
        $this->template->visibility = $this->getVisibility();
        $this->template->language = $this->getVisibility();
        $this->template->has_translation = $this->hasTranslation();
        $this->template->action = $this->getAction();
        $this->template->languages = $this->getLanguageManager()->getAvailableLanguages();
        $this->template->language = $this->getLanguage();
        $this->template->pages = $this->getPageManager()->getFiltered(
            self::TYPE_TABLE[$this->getType()],
            self::VISIBILITY_TABLE[$this->getVisibility()],
            $this->getLanguageManager()->getByCode($this->getLanguage()),
            $this->hasTranslation(),
            $this->getPage(),
            15,//TODO should be an option?
            $this->numberOfPages,
            is_string($post_query = $this->getSearchQuery()) ? $post_query : null);
        $this->template->paginator_page_key = self::PAGE_KEY;

        if($this->isAjax()){ //needs to be here for some reason xd
            $this->redrawControl();
        }
    }

    public function actionDelete() {
        $pm = $this->getPageManager();
        if ($pm->exists($deleteId = $this->getParameter(self::ID_KEY))) {
            try {
                $pm->delete($deleteId);
                $this->flashMessage("admin.page.delete.success");
            } catch (Exception $ex) {
                $this->somethingWentWrong();
            }
        } else {
            $this->flashMessage("admin.page.delete.not_found");
        }
        $this->redirect(302, "show", [self::ID_KEY => null]);
    }

    private function getType(): string {
        return $this->getParameter(self::TYPE_KEY);
    }

    private function getVisibility(): string {
        return $this->getParameter(self::VISIBILITY_KEY);
    }

    private function getLanguage(): ?string {
        return $this->getParameter(self::LANGUAGE_KEY);
    }

    private function hasTranslation(): ?bool {
        return $this->getParameter(self::HAS_TRANSLATION_KEY);
    }

    private function getPage(): int {
        return $this->getParameter(self::PAGE_KEY, 1);
    }

    private function getSearchQuery():?string {
        return $this->getParameter(\FormFactory::PAGE_SHOW_SEARCH_NAME);
    }

    public function actionDefault() {
        $this->redirect(302, "show");
    }

    public function createComponentPageEditForm() {
        $page = $this->getEditPage();
        $form = $this->getFormFactory()->createPageEditForm($page,
            function (BaseControl $url) use ($page) {
                return $this->getPageManager()->isUrlAvailable($url->getValue(), $page->getLang(), $page->getLocalId());
            });
        $form->onSuccess[] = function (Form $form) use ($page) {
            $values = $form->getValues(true);
            try {
                $this->getPageManager()->update(
                    $page,
                    $values[\FormFactory::PAGE_EDIT_GLOBAL_CONTAINER][\FormFactory::PAGE_EDIT_PARENT_NAME],
                    $values[\FormFactory::PAGE_EDIT_LOCAL_CONTAINER][\FormFactory::PAGE_EDIT_TITLE_NAME],
                    $values[\FormFactory::PAGE_EDIT_LOCAL_CONTAINER][\FormFactory::PAGE_EDIT_DESCRIPTION_NAME],
                    $values[\FormFactory::PAGE_EDIT_LOCAL_CONTAINER][\FormFactory::PAGE_EDIT_URL_NAME],
                    $values[\FormFactory::PAGE_EDIT_GLOBAL_CONTAINER][\FormFactory::PAGE_EDIT_GLOBAL_VISIBILITY_NAME],
                    $values[\FormFactory::PAGE_EDIT_LOCAL_CONTAINER][\FormFactory::PAGE_EDIT_LOCAL_VISIBILITY_NAME],
                    $values[\FormFactory::PAGE_EDIT_LOCAL_CONTAINER][\FormFactory::PAGE_EDIT_CONTENT_NAME],
                    $values[\FormFactory::PAGE_EDIT_LOCAL_CONTAINER][\FormFactory::PAGE_EDIT_IMAGE_NAME]
                );

                $this->flashMessage("admin.page.edit.success");
                $this->redirect(302, "Page:show", [self::ID_KEY => null, self::LANGUAGE_KEY => null]);
            } catch (Exception $ex) {
                Debugger::log($ex);
                $this->somethingWentWrong();
            }
        };
        return $form->setAction($this->link("edit", [self::ID_KEY => $page->getGlobalId()]));
    }

    public function createComponentAdminPageSearch() {
        $form = $this->getFormFactory()->createAdminPageSearch($this->getSearchQuery());
        return $form;
    }


    public function createComponentPaginator(string $name) {
        return new PaginatorControl($this, $name, self::PAGE_KEY, $this->getPage(), $this->numberOfPages);
    }

    private function getEditPage(): \Page {
        return $this->template->page;
    }
}