<?php


class PageWrapper {
    private $page;
    private $language;
    private $pageSettings;
    private $pageManager;
    private $languageManager;
    private $settingsManager;

    /**
     * PageWrapper constructor.
     * @param APage $page
     * @param IPageManager $pageManager
     * @param ILanguageManager $languageManager
     * @param ISettingsManager $settingsManager
     */
    public function __construct(APage $page, IPageManager $pageManager, ILanguageManager $languageManager, ISettingsManager $settingsManager) {
        $this->page = $page;
        $this->pageManager = $pageManager;
        $this->languageManager = $languageManager;
        $this->settingsManager = $settingsManager;
    }

    /**
     * @return APage
     */
    private function getPage(): APage {
        return $this->page;
    }


    /**
     * @return Language
     */
    public function getLanguage(): Language {
        return $this->language instanceof Language ? $this->language : $this->language = $this->languageManager->getById($this->getPage()->getLangId());
    }

    /**
     * @return PageSettings
     */
    private function getPageSettings(): PageSettings {
        return $this->pageSettings instanceof PageSettings ? $this->pageSettings : $this->pageSettings = $this->settingsManager->getPageSettings($this->getPage()->getLangId());
    }

    public function isHomePage():bool{
        $page =  $this->pageManager->getHomePage($this->getLanguageId());
        return $page instanceof PageWrapper && $page->getLocalId() === $this->getLocalId();
    }

    private function getLanguageId():int {
        return $this->getPage()->getLangId();
    }

    private function getLocalId():int {
        return $this->getPage()->getLangId();
    }
}