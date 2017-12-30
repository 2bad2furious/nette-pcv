<?php


class HeaderPageOld {
    const TYPE_PAGE = 1;
    const TYPE_CUSTOM = 0;

    /**
     * @var int
     */
    private $headerPageId;

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $title;

    /**
     * @var HeaderPageOld[]
     */
    private $children = [];
    /**
     * @var bool
     */
    private $isActive;
    /**
     * @var int|null
     */
    private $pageId;
    /**
     * @var int
     */
    private $languageId;
    /**
     * @var Language
     */
    private $language;
    /**
     * @var int
     */
    private $position;

    /**
     * HeaderPage constructor.
     * @param int $headerPageId
     * @param null|int $pageId
     * @param int $languageId
     * @param null|string $url
     * @param string|null $title
     * @param int $position
     */
    public function __construct(int $headerPageId, ?int $pageId = null, int $languageId, ?string $url, ?string $title, int $position) {
        $this->headerPageId = $headerPageId;
        $this->url = $url;
        $this->title = $title;
        $this->pageId = $pageId;
        $this->languageId = $languageId;
        $this->position = $position;
    }

    public function setPage(Page $page) {
        if ($this->pageId !== $page->getGlobalId())
            throw new InvalidArgumentException("Pages ids are not the same. {$this->pageId} !== {$page->getGlobalId()}");

        if ($this->getLanguageId() !== $page->getLang()->getId())
            throw new InvalidArgumentException("Languages are not the same");

        $this->setUrl($page->getCompleteUrl());
        if (!$this->getTitle()) $this->setTitle($page->getTitle());
    }

    public function setLanguage(Language $language) {
        if ($this->getLanguageId() !== $language->getId())
            throw new InvalidArgumentException("Language id {$language->getId()} != setId of {$this->getLanguageId()}");

        if ($this->language instanceof Language)
            throw new InvalidState("Language already set");

        $this->language = $language;
    }

    private function setUrl(string $url): void {
        if (is_string($this->url)) throw new Exception("Url already set");
        $this->url = $url;
    }

    private function setTitle(string $title): void {
        if (is_string($this->title)) throw new Exception("Title already set");
        $this->title = $title;
    }

    public function setActive(bool $isActive) {
        if (is_bool($this->isActive)) throw new Exception("Activeness already set");
        $this->isActive = $isActive;
    }

    public function __clone() {
        foreach ($this->children as &$child) {
            $child = clone $child;
        }
    }

    public function addChild(HeaderPageOld $headerPage) {
        $this->children[] = $headerPage;
    }

    /**
     * @return HeaderPageOld[]
     */
    public function getChildren(): array {
        return $this->children;
    }

    /**
     * @return int
     */
    public function getHeaderPageId(): int {
        return $this->headerPageId;
    }

    /**
     * @return string
     */
    public function getUrl(): string {
        return /*TODO think of the business logic for languageCode prepend and stuff */
            $this->url;
    }

    /**
     * @return string
     */
    public function getTitle(): string {
        return $this->title;
    }

    /**
     * @return bool
     */
    public function isActive(): bool {
        return $this->isActive;
    }

    public function getPageId(): ?int {
        return $this->pageId;
    }

    public function getLanguageId(): int {
        return $this->languageId;
    }

    /**
     * @return int
     */
    public function getPosition(): int {
        return $this->position;
    }

    public function getType(): int {
        return is_int($this->getPageId()) ? self::TYPE_PAGE : self::TYPE_CUSTOM;
    }
}