<?php


class HeaderWrapper {
    private $header;
    private $language;
    private $children = [];
    private $page;
    private $active;

    /**
     * HeaderWrapper constructor.
     * @param Header $header
     * @param Language $language
     * @param null|Page $page
     * @param array $children
     * @param bool $active
     */
    public function __construct(Header $header, Language $language, ?Page $page, array $children, bool $active) {
        if ($header->getLanguageId() !== $language->getId())
            throw new InvalidArgumentException("Language {$header->getLanguageId()} expected, {$language->getId()} received.");

        if ($header->getType() === Header::TYPE_PAGE) {
            if (!$page instanceof Page)
                throw new InvalidArgumentException("Page {$header->getPageId()} expected, none received");

            if ($header->getPageId() !== $page->getGlobalId())
                throw new InvalidArgumentException("Page {$header->getPageId()} expected, {$page->getGlobalId()} received");

            if ($page->getLanguageId() !== $header->getLanguageId())
                throw new InvalidArgumentException("Language ");
        } else {
            if ($page instanceof Page)
                throw new InvalidArgumentException("No page expected");
        }

        $this->header = $header;
        $this->language = $language;
        $this->page = $page;
        $this->active = $active;

        foreach ($children as $child) {
            $this->addChild($child);
        }
    }

    private function addChild(HeaderWrapper $child) {
        if ($child->getHeader()->getParentId() !== $this->getHeader()->getId())
            throw new InvalidArgumentException("Header {$child->getHeader()->getId()} is not a child of {$this->getHeader()->getId()}; real parent = {$child->getHeader()->getParentId()}");
        if ($child->getHeader()->getLanguageId() !== $this->getHeader()->getLanguageId())
            throw new InvalidState("Languages of child and parent are not the same - expected:{$this->getHeader()->getLanguageId()}, received: {$child->getHeader()->getLanguageId()} ");

        $this->children[] = $child;
    }

    /**
     * @return Header
     */
    public function getHeader(): Header {
        return $this->header;
    }

    /**
     * @return Language
     */
    public function getLanguage(): Language {
        return $this->language;
    }

    /**
     * @return HeaderWrapper[]
     */
    public function getChildren() {
        return $this->children;
    }

    /**
     * @return Page|null
     */
    public function getPage() {
        return $this->page;
    }

    /**
     * @return bool
     */
    public function isActive(): bool {
        return $this->active;
    }

    //Deletegating methods

    public function getId(): int {
        return $this->getHeader()->getId();
    }

    public function getTitle(): string {
        return $this->getHeader()->getTitle() ?: $this->getPage()->getTitle();
    }

    public function getUrl(): string {
        return $this->getHeader()->getUrl() ?: $this->getPage()->getUrl();
    }

    public function getCompleteUrl(): string {
        return "";//TODO implement
    }

    public function getLanguageId(): int {
        return $this->getHeader()->getLanguageId();
    }

    public function getPageId(): ?int {
        return $this->getHeader()->getPageId();
    }

    public function getPosition(): int {
        return $this->getHeader()->getPosition();
    }

    public function getParentId(): int {
        return $this->getHeader()->getParentId();
    }

    public function getLanguageCode(): int {
        return $this->getLanguage()->getCode();
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
}