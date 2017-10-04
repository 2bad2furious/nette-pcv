<?php


use Nette\Utils\DateTime;

class Page {
    const ID_404 = -1;
    /** @var  int */
    private $global_id;

    /** @var  int */
    private $local_id;

    /** @var  string */
    private $title;

    /** @var  string */
    private $description;

    /** @var string */
    private $url;

    /** @var string */
    private $permanentUrl;

    /** @var Type */
    private $type;

    /** @var string */
    private $content;

    /** @var  string */
    private $hash;
    /**
     * @var UserIdentity
     */
    private $author;
    /**
     * @var DateTime
     */
    private $created;
    /**
     * @var DateTime
     */
    private $edited;
    /**
     * @var string
     */
    private $image;
    /**
     * @var string
     */
    private $imageAlt;
    /**
     * @var Tag[]
     */
    private $tags;
    /**
     * @var int|Page
     */
    private $parent;
    /**
     * @var null|PageSettings
     */
    private $settings;
    /**
     * @var Language
     */
    private $language;
    /**
     * @var int
     */
    private $status;

    /**
     * @var string
     */
    private $tagValues;
    /**
     * @var int
     */
    private $parentId;

    /**
     * Page constructor.
     * @param int $global_id
     * @param int $local_id
     * @param string $title
     * @param string $description
     * @param string $url
     * @param string $permanentUrl
     * @param string $image
     * @param string $imageAlt
     * @param Type $type
     * @param string $content
     * @param Tag[] $tags
     * @param UserIdentity|null $author
     * @param DateTime|null $created
     * @param DateTime|null $edited
     * @param int $status
     * @param Language $language
     * @param int $parentId
     */
    public function __construct(int $global_id, int $local_id, string $title, string $description, string $url, string $permanentUrl, string $image, string $imageAlt, Type $type, string $content, array $tags, ?UserIdentity $author, ?DateTime $created, ?DateTime $edited, int $status, Language $language, int $parentId) {
        $this->global_id = $global_id;
        $this->local_id = $local_id;
        $this->title = $title;
        $this->description = $description;
        $this->url = $url;
        $this->permanentUrl = $permanentUrl;
        $this->type = $type;
        $this->content = $content;
        $this->author = $author;
        $this->created = $created;
        $this->edited = $edited;
        $this->image = $image;
        $this->imageAlt = $imageAlt;
        $this->tags = $tags;
        $this->language = $language;
        $this->status = $status;
        $this->parentId = $parentId;
        $this->tagValues = implode(" ", array_map(function (Tag $item) {
            return $item->getName();
        }, $this->getTags()));
    }

    private function recalculateHash(): void {
        $this->hash = sha1(serialize($this));
    }

    public function setPageSettings(PageSettings $pageGlobals): void {
        if ($this->settings instanceof PageSettings) throw new Exception("PageGlobals already set");

        $this->settings = $pageGlobals;

        $this->recalculateHash();
    }

    public function setParent(Page $parent) {
        if ($this->parent instanceof Page) throw new Exception("Parent already set");
        if ($this->parentId !== $parent->getGlobalId()) throw new Exception("Ids are not the same. {$this->parentId}  !== {$parent->getGlobalId()}");
        if ($this->language->getId() !== $parent->getLang()->getId()) throw new Exception("Languages are not the same");
        $this->parent = $parent;

        $this->recalculateHash();
    }

    public function getGA(): string {
        return $this->settings->getGoogleAnalytics()->getValue();
    }

    public function getSiteName(): string {
        return $this->settings->getSiteName()->getValue();
    }

    public function getWholeTitle(): string {
        $separator = $this->settings->getTitleSeparator()->getValue();

        return $this->getTitle() . (
            $this->getSiteName() && $separator
                /* <title><separator><siteName> */
                ? $separator . $this->getSiteName()
                /* <title> */
                : ""
            );
    }

    public function getTagValues(): string {
        return $this->tagValues;
    }

    public function getSection(): string {
        return $this->parent->getTitle();
    }

    public function isArticle(): bool {
        return is_a($this->getType(), Type::POST_TYPE);
    }

    public function isPage(): bool {
        return is_a($this->getType(), Type::PAGE_TYPE);
    }

    public function __clone() {
        if ($this->parent instanceof Page) $this->parent = clone $this->parent;
    }

    public function is404(): bool {
        return $this->getGlobalId() === self::ID_404;
    }

    public function getCompleteUrl(): string {
        return "/" . $this->getLang()->getCode() . "/" . $this->getUrl();
    }

    /**
     * @return int
     */
    public function getGlobalId(): int {
        return $this->global_id;
    }

    /**
     * @return int
     */
    public function getLocalId(): int {
        return $this->local_id;
    }

    /**
     * @return string
     */
    public function getTitle(): string {
        return $this->title;
    }

    /**
     * @return string
     */
    public function getDescription(): string {
        return $this->description;
    }

    /**
     * @return Language
     */
    public function getLang(): Language {
        return $this->language;
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
    public function getPermanentUrl(): string {
        return $this->permanentUrl;
    }

    /**
     * @return Type
     */
    public
    function getType(): Type {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getContent(): string {
        return $this->content;
    }

    /**
     * @return string|null
     */
    public function getHash(): ?string {
        return $this->hash;
    }

    /**
     * @return UserIdentity|null
     */
    public function getAuthor(): ?UserIdentity {
        return $this->author;
    }

    /**
     * @return DateTime|null
     */
    public function getCreated(): ?DateTime {
        return $this->created;
    }

    /**
     * @return DateTime|null
     */
    public function getEdited(): ?DateTime {
        return $this->edited;
    }

    /**
     * @return string
     */
    public function getImage(): string {
        return $this->image;
    }

    /**
     * @return string
     */
    public function getImageAlt(): string {
        return $this->imageAlt;
    }

    /**
     * @return Tag[]
     */
    public function getTags(): array {
        return $this->tags;
    }

    /**
     * @return int
     */
    public function getStatus(): int {
        return $this->status;
    }

    /**
     * @return null|Page
     */
    public function getParent():?Page {
        return $this->parent;
    }

    /**
     * @return int
     */
    public function getParentId(): int {
        return $this->parent instanceof Page ? $this->parent->getGlobalId() : $this->parentId;
    }
}