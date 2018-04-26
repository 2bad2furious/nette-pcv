<?php

namespace frontModule;

use ArticlePageControl;
use BasePresenter;
use ContentControl;
use HeaderPageControl;
use IFileManager;
use PageManager;
use SectionPageControl;
use Tracy\Debugger;

class PagePresenter extends BasePresenter {
    const PARAM_URL = "url",
        PARAM_ID = "page_id";

    /** @var  \PageWrapper|null */
    private $page;

    public function startup() {
        $this->setDefaultSnippets(["content", "header", "footer"] +
            ($this->getUser()->isLoggedIn()
                ? ["admin-header-bar"]
                : []
            )
        );

        parent::startup();
    }

    /**
     * @var int
     * @persistent
     */
    public $page_id;

    /**
     * @throws \LanguageByCodeNotFound
     * @throws \LanguageByIdNotFound
     */
    public function renderDefault() {
        $url = $this->getParameter(self::PARAM_URL);
        $language = $this->getLocaleLanguage()->getId();

        $this->page = $this->getPageManager()->getByUrl($language, (string)$url);
        if ($this->page instanceof \PageWrapper && ($this->page->isHomePage() || $this->page->is404()))
            $this->redirect(301, "home", [self::PARAM_URL => null]);

        $this->prepareTemplate();
    }

    /**
     * @throws \LanguageByCodeNotFound
     * @throws \LanguageByIdNotFound
     */
    public function actionPermanent() {
        $id = $this->getParameter(self::PARAM_ID);

        $this->page = $this->getPageManager()->getByGlobalId($this->getLocaleLanguage()->getId(), $id);
        if (!$this->page instanceof \PageWrapper || $this->page->is404() || $this->page->isHomePage())
            $this->redirect(302, "home", [self::PARAM_ID => null]);

        $this->redirect(302, "default", [self::PARAM_URL => $this->page->getUrl(), self::PARAM_ID => null]);
    }

    /**
     * @throws \LanguageByCodeNotFound
     * @throws \LanguageByIdNotFound
     */
    protected function prepareTemplate() {
        $page = $this->page;

        if (!$page instanceof \PageWrapper || (!$page->isVisible() && !$this->getUser()->isAllowed(\IPageManager::ACTION_SEE_NON_PUBLIC_PAGES)))
            $this->page = $page = $this->getPageManager()->get404($this->getLocaleLanguage()->getId());

        $this->getHttpResponse()->setCode($page->is404() ? 404 : 200);

        $this->template->page = $page;
        $this->payload->title = $page->getTitle();
        $this->template->isLoggedIn = $this->getUser()->isLoggedIn();
        $this->template->setFile(__DIR__ . "/templates/Page/default.latte");
    }

    /**
     * @throws \LanguageByCodeNotFound
     * @throws \LanguageByIdNotFound
     */
    public function renderHome() {
        $language = $this->getLocaleLanguage();

        $this->page = $this->getPageManager()->getByGlobalId($language->getId(), $language->getHomepageId());
        $this->prepareTemplate();
    }

    protected function getAllowedRoles(): array {
        return \IAccountManager::ROLES;
    }

    public function createComponentContent(string $name): ContentControl {
        return new ContentControl($this->getPage(), $this, $name);
    }

    /**
     * @param string $name
     * @return ArticlePageControl
     */
    public function createComponentArticlePage(string $name): ArticlePageControl {
        return new ArticlePageControl($this, $name);
    }

    public function createComponentHeader(string $name): HeaderPageControl {
        return new HeaderPageControl($this->page, $this, $name);
    }

    public function getPage(): \PageWrapper {
        return $this->page;
    }

    public function createComponentAdminBar(string $name): \AdminBarControl {
        return new \AdminBarControl($this->page, $this, $name);
    }

    public function createComponentBreadcrumbs(string $name) {
        return new \BreadCrumbsControl($this->getPage(), $this, $name);
    }

    public function createComponentFooter(string $name): \FooterPageControl {
        return new \FooterPageControl($this->getPage(), $this, $name);
    }
}