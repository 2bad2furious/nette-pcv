<?php


class HeaderPage {
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
     * @var HeaderPage[]
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
     * @var Language
     */
    private $language;

    /**
     * HeaderPage constructor.
     * @param int $headerPageId
     * @param null|int $pageId
     * @param Language $language
     * @param null|string $url
     * @param string|null $title
     */
    public function __construct(int $headerPageId, ?int $pageId = null, Language $language, ?string $url = null, ?string $title = null) {
        $this->headerPageId = $headerPageId;
        $this->url = $url;
        $this->title = $title;
        $this->pageId = $pageId;
        $this->language = $language;
    }

    public function setPage(Page $page) {
        if ($this->pageId !== $page->getGlobalId()) throw new Exception("Pages ids are not the same. {$this->pageId} !== {$page->getGlobalId()}");
        if ($this->language->getId() !== $page->getLang()->getId()) throw new Exception("Languages are not the same");
        $this->setUrl($page->getCompleteUrl());
        $this->setTitle($page->getTitle());
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

    public function addChild(HeaderPage $headerPage) {
        $this->children[] = $headerPage;
    }

    /**
     * @return HeaderPage[]
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
        return $this->url;
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
}