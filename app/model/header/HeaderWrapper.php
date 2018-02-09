<?php

/**
 * Class HeaderWrapper
 * @method int getId()
 * @method int getPageId()
 * @method int getPosition()
 * @method int getLanguageId()
 * @method int getParentId()
 * @method int[] getChildrenIds()
 */
class HeaderWrapper {
    private $header;
    private $language;
    private $children;
    private $page;
    private $active;
    private $pageManager;
    private $headerManager;
    private $languageManager;

    /**
     * HeaderWrapper constructor.
     * @param Header $header
     * @param ILanguageManager $languageManager
     * @param IPageManager $pageManager
     * @param IHeaderManager $headerManager
     */
    public function __construct(Header $header, ILanguageManager $languageManager, IPageManager $pageManager, IHeaderManager $headerManager) {
        if ($header->getType() === Header::TYPE_PAGE) {
            $pageManager->exists($header->getPageId(), $header->getLanguageId());
        }

        $this->header = $header;
        $this->pageManager = $pageManager;
        $this->headerManager = $headerManager;
        $this->languageManager = $languageManager;

        if (!$this->isPage()) $this->page = false;
    }

    /**
     * @return Header
     */
    public function getHeader(): Header {
        return $this->header;
    }


    public function getLanguage(): Language {
        if (!$this->language instanceof Language) {
            $this->language = $this->languageManager->getById($this->getLanguageId());
        }
        return $this->language;
    }

    /**
     * @return HeaderWrapper[]
     * @throws InvalidState
     */
    public function getChildren() {
        if ($this->children === null) {
            $this->children = array_map(function (int $childId) {
                return $this->headerManager->getById($childId);
            }, $this->getChildrenIds());
        }
        return $this->children;
    }

    /**
     * @return null|PageWrapper
     * @throws InvalidState
     */
    public function getPage() {
        if ($this->page !== false) {
            $this->page = $this->pageManager->getByGlobalId($this->getLanguageId(), $this->getPageId(), false);
        }
        return $this->page instanceof PageWrapper ? $this->page : null;
    }

    /**
     * @param null|PageWrapper $page
     * @return bool
     */
    public function isActive(?PageWrapper $page = null): bool {
        if (!is_bool($this->active)) {
            if ($page === null) $this->active = false;
            else if ($page->getGlobalId() === $this->getPageId()) $this->active = true;
            else {
                $active = false;
                foreach ($this->getChildren() as $child) {
                    if ($child->isActive($page)) $active = true;
                }
                $this->active = $active;
            }
        }
        return $this->active;
    }

    /**
     * @return string
     * @throws InvalidState
     */
    public function getUrl(): string {
        dump($this);
        if ($this->isPage())
            return $this->getPage()->getCheckedUrl();

        return $this->getHeader()->getUrl();
    }

    /**
     * @return string
     * @throws InvalidState
     */
    public function getTitle(): string {
        if ($this->isPage())
            return $this->getHeader()->getTitle() ?:
                $this->getPage()->isTitleDefault() ? "" : $this->getPage()->getTitle(); //checking whether the title is set

        return $this->getHeader()->getTitle();
    }

    /**
     * @param bool $prependSlash
     * @return string
     * @throws InvalidState
     * @throws LanguageByIdNotFound
     */
    public function getCompleteUrl(bool $prependSlash = true): string {
        if ($this->isPage())
            return $this->getPage()->getCompleteUrl($prependSlash);

        return ($prependSlash ? "/" : "") . $this->getLanguage()->getCode() . "/" . $this->getUrl();
    }

    public function getType(): int {
        return $this->getHeader()->getType();
    }

    public function isPage(): bool {
        return $this->getType() === Header::TYPE_PAGE;
    }

    public function isCustom(): bool {
        return $this->getType() === Header::TYPE_CUSTOM;
    }

    public function __call(string $name, array $arguments) {
        return call_user_func_array([$this->getHeader(), $name], $arguments);
    }

    public function isTitleCustom(): bool {
        return !!$this->getHeader()->getTitle();
    }

    public function canMoveUp(): bool {
        return $this->headerManager->canBeMovedUp($this->getId());
    }

    public function canMoveDown(): bool {
        return $this->headerManager->canBeMovedDown($this->getId());
    }

    public function canMoveLeft(): bool {
        return $this->headerManager->canBeMovedLeft($this->getId());
    }

    public function canMoveRight(): bool {
        return $this->headerManager->canBeMovedRight($this->getId());
    }

    /**
     * checks whether it has page if it needs it
     * used to not make the node rendered if eg. the page is not visible
     * @return bool
     */
    public function isOk(): bool {
        return !$this->isPage() || ($this->getPage() instanceof PageWrapper && $this->getPage()->isVisibile());
    }
}