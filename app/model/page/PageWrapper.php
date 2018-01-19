<?php

/**
 * Class PageWrapper
 * @method int getGlobalId()
 * @method int getLocalId()
 * @method int getStatus()
 * @method int getGlobalStatus()
 * @method int getLocalStatus()
 * @method string getUrl()
 * @method string getTitle()
 * @method string getDescription()
 * @method int getImageId()
 * @method DateTime getCreated()
 * @method DateTime getEdited()
 * @method string getContent()
 * @method int getLanguageId()
 * @method int getAuthorId()
 * @method int getParentId()
 * @method string getOgType()
 * @method bool isPost()
 * @method bool isPage()
 * @method bool is404()
 */
class PageWrapper {
    private $page;
    private $language;
    private $pageManager;
    private $languageManager;
    private $settingsManager;
    private $userManager;
    private $mediaManager;

    /**
     * PageWrapper constructor.
     * @param APage $page
     * @param IPageManager $pageManager
     * @param ILanguageManager $languageManager
     * @param ISettingsManager $settingsManager
     * @param IUserManager $userManager
     * @param IMediaManager $mediaManager
     */
    public function __construct(APage $page, IPageManager $pageManager, ILanguageManager $languageManager, ISettingsManager $settingsManager, IUserManager $userManager, IMediaManager $mediaManager) {
        $this->page = $page;
        $this->pageManager = $pageManager;
        $this->languageManager = $languageManager;
        $this->settingsManager = $settingsManager;
        $this->userManager = $userManager;
        $this->mediaManager = $mediaManager;
    }

    /**
     * @return APage
     */
    private function getPage(): APage {
        return $this->page;
    }


    /**
     * @return Language
     * @throws LanguageByIdNotFound
     */
    public function getLanguage(): Language {
        if (!$this->language instanceof Language) {
            $this->language = $this->languageManager->getById($this->getLanguageId());
        }
        return $this->language;
    }

    public function getAuthor(): ?UserIdentity {
        return $this->userManager->getUserIdentityById($this->getAuthorId());
    }

    /**
     * @return bool
     * @throws LanguageByIdNotFound
     */
    public function isHomePage(): bool {
        return $this->getLanguage()->getHomepageId() == $this->getGlobalId();
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments) {
        return call_user_func_array([$this->getPage(), $name], $arguments);
    }

    /**
     * @param bool $prependSlash
     * @return string
     * @throws LanguageByIdNotFound
     */
    public function getCompleteUrl(bool $prependSlash = true): string {
        return ($prependSlash ? "/" : "") . $this->getLanguage()->getCode() . "/" . $this->getUrl();
    }

    public function isVisibile(): bool {
        return $this->getStatus() === PageManager::STATUS_PUBLIC;
    }

    /**
     * @return string
     * @throws LanguageByIdNotFound
     */
    public function getCompleteTitle(): string {
        $separator = $this->getLanguage()->getTitleSeparator();
        $siteTitle = $this->getLanguage()->getSiteName();

        return $this->getTitle() . " " . (
            $siteTitle && $separator
                /* <title><separator><siteName> */
                ? $separator . " " . $siteTitle
                /* <title> */
                : "" . $siteTitle
            );
    }

    /**
     * @param bool $prependSlash
     * @return string
     * @throws LanguageByIdNotFound
     */
    public function getPermanentUrl(bool $prependSlash = true): string {
        return ($prependSlash ? "/" : "") . $this->getLanguage()->getCode() . "/" . PageManager::PAGE_URL_PERMANENT . "/" . $this->getGlobalId();
    }

    public function getImage(): ?Media {
        if (!($imageId = $this->getImageId())) return null;
        return $this->mediaManager->getById($imageId, MediaManager::TYPE_IMAGE);
    }

    /**
     * @return string
     * @throws LanguageByIdNotFound
     */
    public function getSiteName(): string {
        return $this->getLanguage()->getSiteName();
    }

    /**
     * @return string
     * @throws LanguageByIdNotFound
     */
    public function getGA(): string {
        return $this->getLanguage()->getGa();
    }

    public function isTitleDefault(): bool {
        return $this->getTitle() === PageManager::DEFAULT_TITLE;
    }

    public function getCheckedUrl() {
        if ($this->isUrlGenerated()) return "";
        return $this->getUrl();
    }

    public function isUrlGenerated(): bool {
        return PageManager::isDefaultUrl($this->getUrl());
    }
}