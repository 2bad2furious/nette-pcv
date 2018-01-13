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
            $pageManager->exists($header->getPageId(), true);
        }

        if (!$header->getPageId()) $this->page = false;
        $this->header = $header;
        $this->pageManager = $pageManager;
        $this->headerManager = $headerManager;
        $this->languageManager = $languageManager;
    }

    /**
     * @return Header
     */
    public function getHeader(): Header {
        return $this->header;
    }

    /**
     * @return Language
     * @throws InvalidState
     */
    public function getLanguage(): Language {
        if (!$this->language instanceof Language) {
            $language = $this->languageManager->getById($this->getLanguageId());
            if (!$language->getId() !== $this->getLanguageId()) throw new InvalidState("Language Ids not the same; {$language->getId()} !== {$this->getLanguageId()}");
            $this->language = $language;
        }
        return $this->language;
    }

    /**
     * @return HeaderWrapper[]
     * @throws InvalidState
     */
    public function getChildren() {
        if ($this->children === null) {
            $this->children = [];
            foreach ($this->getChildrenIds() as $childrenId) {
                $child = $this->headerManager->getById($childrenId);

                if ($child->getLanguageId() !== $this->getLanguageId()) throw new InvalidState("Languages are not the same; {$child->getLanguageId()} !== {$this->getLanguageId()}");
                if ($child->getParentId() !== $this->getId()) throw new InvalidState("Child is not actual child of this; {$child->getParentId()} !== {$this->getId()}");

                $this->children[] = $child;
            }
        }
        return $this->children;
    }

    /**
     * @return null|PageWrapper
     * @throws InvalidState
     */
    public function getPage() {
        if ($this->page !== false) {
            $page = $this->pageManager->getByGlobalId($this->getLanguageId(), $this->getPageId());

            if ($page->getGlobalId() !== $this->getPageId())
                throw new InvalidState("Page Ids are not the same; {$page->getGlobalId()} !== {$this->getPageId()}");
            if ($page->getLanguageId() !== $this->getLanguageId())
                throw new InvalidState("Language Ids are not the same; {$page->getLanguageId()} !== {$this->getLanguageId()}");

            $this->page = $page;
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

    public function getUrl(): string {
        if ($this->isPage())
            return $this->getPage()->getUrl();

        return $this->getHeader()->getUrl();
    }

    public function getTitle(): string {
        if ($this->isPage())
            return $this->getHeader()->getTitle() ?: $this->getPage()->getTitle();

        return $this->getHeader()->getTitle();
    }

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
}