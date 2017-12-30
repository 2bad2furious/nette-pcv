<?php


class HeaderPage {
    const TYPE_PAGE = 1;
    const TYPE_CUSTOM = 0;

    private $id;
    private $title;
    private $url;
    private $position;
    private $pageId;
    private $languageId;
    private $language;
    private $childrenIds;
    private $children;


    /**
     * HeaderPage constructor.
     * @param int $id
     * @param null|string $title
     * @param null|string $url
     * @param int|null $pageId
     * @param int $languageId
     * @param int $position
     * @param array $childrenIds
     */
    public function __construct(int $id, ?string $title, ?string $url, ?int $pageId, int $languageId, int $position, array $childrenIds) {
        $this->id = $id;
        $this->title = $title;
        $this->url = $url;
        $this->pageId = $pageId;
        $this->languageId = $languageId;
        $this->position = $position;

        foreach($childrenIds as $childrenId){
            $this->childrenIds
        }

    }

    private function addChildId(){

    }

    public function setPage(Page $page) {
        if ($page->getGlobalId() !== $this->getPageId())
            throw new InvalidArgumentException("Page {$this->getPageId()} expected, {$page->getGlobalId()} received.");
        if ($page->getLanguageId() !== $this->getLanguageId())
            throw new InvalidArgumentException("Language {$this->getLanguageId()} expected, {$page->getLanguageId()} received");

        $this->setUrl($page->getUrl());
        if (!$this->getTitle()) $this->setTitle($page->getTitle());
    }

    private function setTitle(string $title) {
        if ($this->getTitle()) throw new InvalidState("Title already set");
        $this->title = $title;
    }

    private function setUrl(string $url) {
        if ($this->getUrl()) throw new InvalidState("Url already set");
        $this->url = $url;
    }

    public function setLanguage(Language $language) {
        if ($this->getLanguageId() !== $language->getId())
            throw new InvalidArgumentException("Language {$this->getLanguageId()} expected, {$language->getId()} received.");
        if ($this->getLanguage() instanceof Language)
            throw new InvalidState("Language already set.");

        $this->language = $language;
    }

    public function getType(): int {
        return is_int($this->getPageId()) ? self::TYPE_PAGE : self::TYPE_CUSTOM;
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @return null|string
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * @return null|string
     */
    public function getUrl() {
        return $this->url;
    }

    public function getCompleteUrl(){
        return ""; //TODO check relative and absolute links including language and stuff
    }

    /**
     * @return int
     */
    public function getPosition(): int {
        return $this->position;
    }

    /**
     * @return int|null
     */
    public function getPageId() {
        return $this->pageId;
    }

    /**
     * @return int
     */
    public function getLanguageId(): int {
        return $this->languageId;
    }

    /**
     * @return mixed
     */
    public function getLanguage() {
        return $this->language;
    }
}