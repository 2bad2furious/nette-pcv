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
 * @method bool getDisplayTitle()
 * @method bool getDisplayBreadCrumbs()
 * @method int[] getChildrenIds()
 */
class PageWrapper {
    private $page;
    private $language;
    private $pageManager;
    private $languageManager;
    private $settingsManager;
    private $userManager;
    private $mediaManager;
    private $favicon;
    private $logo;
    private $parent;
    private $children;

    /**
     * PageWrapper constructor.
     * @param APage $page
     * @param IPageManager $pageManager
     * @param ILanguageManager $languageManager
     * @param ISettingsManager $settingsManager
     * @param IAccountManager $userManager
     * @param IFileManager $mediaManager
     */
    public function __construct(APage $page, IPageManager $pageManager, ILanguageManager $languageManager, ISettingsManager $settingsManager, IAccountManager $userManager, IFileManager $mediaManager) {
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
        if (is_int($authId = $this->getAuthorId()))
            return $this->userManager->getUserIdentityById($authId);
        return null;
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

    public function isVisible(): bool {
        return $this->getStatus() === IPageManager::STATUS_PUBLIC;
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

    /**
     * @return File|null
     * @throws FileNotFoundById
     */
    public function getImage(): ?File {
        if (!($imageId = $this->getImageId())) return null;
        return $this->mediaManager->getById($imageId, FileManager::TYPE_IMAGE, false);
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
        return $this->getTitle() === $this->pageManager->getDefaultTitle();
    }

    public function getCheckedUrl() {
        if ($this->isUrlGenerated()) return "";
        return $this->getUrl();
    }

    public function isUrlGenerated(): bool {
        return $this->pageManager->isDefaultUrl($this->getUrl());
    }

    /**
     * @return Image|null
     * @throws LanguageByIdNotFound
     * @throws FileNotFoundById
     */
    public function getFavicon(): ?Image {
        if ($this->favicon === null) {
            $favId = $this->getLanguage()->getFaviconId();
            $this->favicon = ($favId) ? $this->mediaManager->getById($favId, IFileManager::TYPE_IMAGE, false) ?: false : false;
        }
        return $this->favicon instanceof Image ? $this->favicon : null;
    }

    /**
     * @return Image|null
     * @throws FileNotFoundById
     * @throws LanguageByIdNotFound
     */
    public function getLogo(): ?Image {
        if ($this->logo === null) {
            $logoId = $this->getLanguage()->getLogoId();
            $this->logo = ($logoId) ? $this->mediaManager->getById($logoId, IFileManager::TYPE_IMAGE, false) ?: false : false;
        }
        return $this->logo instanceof Image ? $this->favicon : null;
    }

    public function getShortcode() {
        return "[link page_id=" . $this->getGlobalId() . " lang_id=" . $this->getLanguageId() . "]";
    }

    /**
     * @return null|PageWrapper
     * @throws LanguageByIdNotFound
     */
    public function getParent(): ?PageWrapper {
        if ($this->isHomePage()) return null;
        if ($this->parent === null) {
            $this->parent =
                ($p =
                    ($this->getParentId() === null && $this->getLanguage()->getHomepageId() > 0
                        ? $this->pageManager->getByGlobalId($this->getLanguageId(), $this->getLanguage()->getHomepageId())
                        : $this->pageManager->getByGlobalId($this->getLanguageId(), (int)$this->getParentId(), false)
                    )
                ) instanceof PageWrapper
                    ? $p
                    : false;
        }
        return $this->parent instanceof PageWrapper ? $this->parent : null;
    }

    public function getChildren(): array {
        if (!is_array($this->children)) {
            $this->children = array_map(function (int $id) {
                return $this->pageManager->getByGlobalId($this->getLanguageId(), $id);
            }, $this->getChildrenIds());
        }
        return $this->children;
    }

    public function getTagValues(): string {
        return "";
    }

    public function is404(): bool {
        return $this->getGlobalId() === $this->getLanguage()->getErrorpageId();
    }

    public function getOtherTranslation(int $otherLangId): PageWrapper {
        if ($otherLangId === $this->getLanguageId()) return $this;

        return $otherTranslation = $this->pageManager->getByGlobalId($otherLangId, $this->getGlobalId(), true);
    }
}