<?php


namespace adminModule;


use Kdyby\Translation\Translator;
use Nette\Http\IResponse;
use PageManager;
use PaginatorControl;

class PagePresenter extends AdminPresenter {
    const VISIBILITY_KEY = "visibility",
        VISIBILITY_ALL = "all",
        VISIBILITY_PUBLIC = "public",
        VISIBILITY_DRAFT = "draft",
        VISIBILITY_DELETED = "deleted",
        VISIBILITIES = [self::VISIBILITY_ALL, self::VISIBILITY_PUBLIC, self::VISIBILITY_DRAFT, self::VISIBILITY_DELETED],

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
        TYPE_SECTION = "section",
        TYPES = [self::TYPE_ALL, self::TYPE_PAGE, self::TYPE_POST, self::TYPE_SECTION],

        DEFAULT_TYPE = self::TYPE_ALL,

        TYPE_TABLE = [
        self::TYPE_ALL     => PageManager::TYPE_ALL,
        self::TYPE_PAGE    => PageManager::TYPE_PAGE,
        self::TYPE_POST    => PageManager::TYPE_POST,
        self::TYPE_SECTION => PageManager::TYPE_SECTION,
    ],
        PAGE_KEY = "page",

        LANGUAGE_KEY = "language",
        LANGUAGE_ALL = "all",

        DEFAULT_LANGUAGE = self::LANGUAGE_ALL,

        HAS_TRANSLATION_KEY = "has_translation",

        EDIT_ID_KEY = "page_id";

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
                return \UserManager::ROLES_PAGE_DRAFTING;
            case "edit":
                return \UserManager::ROLES_PAGE_DRAFTING;
        }
    }

    public function actionCreate() {
        if (!$this->isRefererOk("show")) $this->error("Bad referer", IResponse::S403_FORBIDDEN);
        $globalId = $this->getPageManager()->addEmpty(self::TYPE_TABLE[$this->getParameter(self::TYPE_KEY)]);
        $this->redirect(302, "edit", [self::EDIT_ID_KEY => $globalId, self::TYPE_KEY => null]);
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
        if ($this->isAjax()) {
            $this->redrawControl();
        }
    }

    private function getType(): string {
        return $this->getParameter(self::TYPE_KEY);
    }

    private function getVisibility(): string {
        return $this->getParameter(self::VISIBILITY_KEY);
    }

    private function getLanguage(): string {
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

    private function getPageManager(): PageManager {
        return $this->context->getByType(PageManager::class);
    }

    public function createComponentPageEditForm() {
        $globalId = $this->getParameter(self::EDIT_ID_KEY);
        $langCode = $this->getParameter(self::LANGUAGE_KEY);
        $language = $this->getLanguageManager()->getByCode($langCode);

        $page = $this->getPageManager()->getByGlobalId($language, $globalId);
        if(!$page instanceof \Page) {
            $this->flashMessage("admin.page.edit.page_not_found");
            $this->redirect(302, "show");
        }
        $form = $this->getFormFactory()->createPageEditForm($page);
        $form->onSubmit[] = function () {
            diedump(func_get_args());
        };
        return $form->setAction($this->link("edit", [self::EDIT_ID_KEY => $globalId]));
    }

    public function createComponentAdminPageSearch() {
        $form = $this->getFormFactory()->createAdminPageSearch($this->getSearchQuery());
        return $form;
    }

    public function createComponentPaginator(string $name) {
        return new PaginatorControl($this, $name, self::PAGE_KEY, $this->getPage(), $this->numberOfPages);
    }
}