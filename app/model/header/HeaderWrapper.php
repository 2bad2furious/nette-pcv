<?php


class HeaderWrapper {
    private $header;
    private $language;
    private $children;
    private $page;
    private $active;
    private $pageManager;
    /**
     * @var IHeaderManager
     */
    private $headerManager;

    /**
     * HeaderWrapper constructor.
     * @param Header $header
     * @param Language $language
     * @param IPageManager $pageManager
     * @param IHeaderManager $headerManager
     */
    public function __construct(Header $header, Language $language, IPageManager $pageManager, IHeaderManager $headerManager) {
        if ($header->getLanguageId() !== $language->getId())
            throw new InvalidArgumentException("Language {$header->getLanguageId()} expected, {$language->getId()} received.");

        if ($header->getType() === Header::TYPE_PAGE) {
            $pageManager->exists($header->getPageId(),true);
        }

        $this->header = $header;
        $this->language = $language;
        $this->pageManager = $pageManager;
        $this->headerManager = $headerManager;
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
        if($this->children === null){
            //TODO check for page's status
            $this->children = $this->headerManager->getChildren($this->getHeader()->getId());
        }
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