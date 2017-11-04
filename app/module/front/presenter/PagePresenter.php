<?php

namespace frontModule;

use ArticlePageControl;
use BasePresenter;
use ContentControl;
use HeaderPageControl;
use Language;
use Nette\Application\BadRequestException;
use Page;
use PageManager;
use SectionPageControl;
use StdClass;
use Tag;

class PagePresenter extends BasePresenter {

    public function renderDefault() {
        $url = $this->getParameter("url");
        $language = $this->getLocaleLanguage();

        $page = $this->getPageManager()->getByUrl($language, (string)$url);
        $this->prepareTemplate($page);
    }

    public function actionPermanent() {
        $id = $this->getParameter("page_id");
        $page = $this->getPageManager()->getByGlobalId($this->getLocaleLanguage(), $id);
        $this->prepareTemplate($page);
    }

    protected function prepareTemplate(?Page $page) {
        if ($page instanceof Page) {
            $this->template->page = $page;
        } else {
            $this->template->page = $this->getPageManager()->getDefault404($this->translator);
        }
        $this->template->setFile(__DIR__ . "/templates/Page/default.latte");
    }

    protected function getAllowedRoles(): array {
        return \UserManager::ROLES;
    }

    protected function getPageManager(): PageManager {
        return $this->context->getByType(PageManager::class);
    }

    public function createComponentContent(string $name): ContentControl {
        return new ContentControl($this, $name);
    }

    /**
     * @param string $name
     * @return ArticlePageControl
     */
    public function createComponentArticlePage(string $name): ArticlePageControl {
        return new ArticlePageControl($this, $name);
    }

    public function createComponentHeader(string $name): HeaderPageControl {
        return new HeaderPageControl($this, $name);
    }

    public function createComponentSectionPage(string $name): SectionPageControl {
        return new SectionPageControl($this, $name);
    }
}