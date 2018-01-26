<?php

namespace frontModule;

use ArticlePageControl;
use BasePresenter;
use ContentControl;
use HeaderPageControl;
use PageManager;
use SectionPageControl;

class PagePresenter extends BasePresenter {
    const PARAM_URL = "url",
        PARAM_ID = "page_id";

    /** @var  \PageWrapper|null */
    private $page;

    public function startup() {
        parent::startup(); // TODO: Change the autogenerated stub
    }

    /**
     * @throws \LanguageByCodeNotFound
     * @throws \LanguageByIdNotFound
     */
    public function renderDefault() {
        $url = $this->getParameter(self::PARAM_URL);
        $language = $this->getLocaleLanguage()->getId();

        $this->page = $this->getPageManager()->getByUrl($language, (string)$url);
        if ($this->page->isHomePage()) $this->redirect(301, "Home", [self::PARAM_URL => null]);
        $this->prepareTemplate();
    }

    /**
     * @throws \LanguageByCodeNotFound
     * @throws \LanguageByIdNotFound
     */
    public function actionPermanent() {
        $id = $this->getParameter(self::PARAM_ID);
        $this->page = $this->getPageManager()->getByGlobalId($this->getLocaleLanguage()->getId(), $id);
        $this->prepareTemplate();
    }

    /**
     * @throws \LanguageByCodeNotFound
     * @throws \LanguageByIdNotFound
     */
    protected function prepareTemplate() {
        $page = $this->page;

        if (!$page instanceof \PageWrapper)
            $page = $this->getPageManager()->get404($this->getLocaleLanguage()->getId());

        $this->template->page = $page;
        $this->payload->title = $page->getTitle();
        $this->template->setFile(__DIR__ . "/templates/Page/default.latte");
    }

    /**
     * @throws \LanguageByCodeNotFound
     * @throws \LanguageByIdNotFound
     */
    public function renderHome() {
        $language = $this->getLocaleLanguage();
        $pageId = $language->getHomepageId();

        $this->page = $this->getPageManager()->getByGlobalId($language->getId(), $pageId);

        $this->prepareTemplate();
    }

    protected
    function getAllowedRoles(): array {
        return \UserManager::ROLES;
    }

    public
    function createComponentContent(string $name): ContentControl {
        return new ContentControl($this, $name);
    }

    /**
     * @param string $name
     * @return ArticlePageControl
     */
    public
    function createComponentArticlePage(string $name): ArticlePageControl {
        return new ArticlePageControl($this, $name);
    }

    public
    function createComponentHeader(string $name): HeaderPageControl {
        return new HeaderPageControl($this->page, $this, $name);
    }

    public
    function createComponentSectionPage(string $name): SectionPageControl {
        return new SectionPageControl($this, $name);
    }
}