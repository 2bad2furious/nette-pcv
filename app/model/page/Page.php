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

    /** @var Type */
    private $type;

    /** @var string */
    private $content;
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
     * @var null|Media
     */
    private $image;
    /**
     * @var Tag[]
     */
    private $tags = [];
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
     * @var int
     */
    private $parentId;
    /**
     * @var int
     */
    private $global_status;
    /**
     * @var int
     */
    private $local_status;
    /**
     * @var int
     */
    private $imageId;
    /**
     * @var int
     */
    private $languageId;
    /**
     * @var int
     */
    private $authorId;

    /**
     * Page constructor.
     * @param int $global_id
     * @param int $local_id
     * @param string $title
     * @param string $description
     * @param string $url
     * @param int $imageId
     * @param Type $type
     * @param string $content
     * @param int $authorId
     * @param DateTime|null $created
     * @param DateTime|null $edited
     * @param int $global_status
     * @param int $local_status
     * @param int $languageId
     * @param int $parentId
     * @throws Exception
     */
    public function __construct(int $global_id, int $local_id, string $title, string $description, string $url, int $imageId, Type $type, string $content, int $authorId, DateTime $created, DateTime $edited, int $global_status, int $local_status, int $languageId, ?int $parentId) {
        $this->global_id = $global_id;
        $this->local_id = $local_id;
        $this->title = $title;
        $this->description = $description;
        $this->url = $url;
        $this->type = $type;
        $this->content = $content;
        $this->authorId = $authorId;
        $this->created = $created;
        $this->edited = $edited;
        $this->status = min($global_status, $local_status);
        $this->parentId = $parentId;
        $this->global_status = $global_status;
        $this->local_status = $local_status;
        if ($parentId === null && $this->isPage()) throw new Exception("Page without parent");
        $this->imageId = $imageId;
        $this->languageId = $languageId;
    }

    public function setAuthor(UserIdentity $identity) {
        if ($this->authorId !== $identity->getId()) throw new Exception("Ids are not the same");
        if ($this->author instanceof $identity) throw new Exception("Author already set");

        $this->author = $identity;
    }

    public function setPageSettings(PageSettings $pageGlobals): void {
        if ($this->settings instanceof PageSettings) throw new Exception("PageGlobals already set");

        $this->settings = $pageGlobals;
    }

    public function setLanguage(Language $language) {
        if ($language->getId() !== $this->languageId) throw new Exception("Language is not the same");
        if ($this->language instanceof Language) throw new Exception("Language already set");

        $this->language = $language;
    }

    public function setImage(Media $media) {
        if ($media->getId() !== $this->imageId) throw new Exception("Ids are not the same");
        if (!$media->isImage()) throw new Exception("Not an image");
        if ($this->image instanceof Media) throw new Exception("Image already set");
        $this->image = $media;
    }

    public function setParent(Page $parent) {
        if ($this->parent instanceof Page) throw new Exception("Parent already set");
        if ($this->parentId !== $parent->getGlobalId()) throw new Exception("Ids are not the same. {$this->parentId}  !== {$parent->getGlobalId()}");
        if ($this->language->getId() !== $parent->getLang()->getId()) throw new Exception("Languages are not the same");
        $this->parent = $parent;
    }

    public function getGA(): string {
        return $this->settings->getGoogleAnalytics();
    }

    public function getSiteName(): string {
        return $this->settings->getSiteName();
    }

    public function getWholeTitle(): string {
        $separator = $this->settings->getTitleSeparator();

        return $this->getTitle() . " " . (
            $this->getSiteName() && $separator
                /* <title><separator><siteName> */
                ? $separator . " " . $this->getSiteName()
                /* <title> */
                : "" . $this->getSiteName()
            );
    }

    public function isTitleDefault(): bool {
        return $this->getTitle() === PageManager::DEFAULT_TITLE;
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

    public function addTags(array $tags) {
        foreach ($tags as $tag) {
            $this->addTag($tag);
        }
    }

    public function addTag(Tag $tag) {
        $this->tags[] = $tag;
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
     * @param bool $check
     * @return string
     */
    public function getUrl(bool $check = false): string {
        if ($check && $this->isUrlGenerated()) return "";
        return $this->url;
    }

    /**
     * checks whether the current url is generated
     * @return string
     */
    public function getCheckedUrl(): string {
        return $this->getUrl(true);
    }

    public function isUrlGenerated(): bool {
        return PageManager::isDefaultUrl($this->getUrl());
    }

    /**
     * @return string
     */
    public function getPermanentUrl(): string {
        $permanent = PageManager::PAGE_URL_PERMANENT;
        return "{$this->getLang()->getCode()}/{$permanent}/{$this->getGlobalId()}";
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
     * @return Media
     */
    public function getImage(): ?Media {
        return $this->image;
    }

    /**
     * @return Tag[]
     */
    public function getTags(): array {
        return $this->tags;
    }

    public function getTagValues(): string {
        return implode(", ", array_map(function (Tag $tag) {
            return $tag->getName();
        }, $this->getTags()));
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
        return $this->parent instanceof Page ? clone $this->parent : $this->parent;
    }

    /**
     * @return int|null
     */
    public function getParentId(): ?int {
        return $this->parentId;
    }

    public function getContentPreview(int $length = 80, bool $stripTags = true): string {
        $content = $this->getContent();
        throw new \Nette\NotImplementedException();
    }

    /**
     * @return int
     */
    public function getGlobalStatus(): int {
        return $this->global_status;
    }

    /**
     * @return int
     */
    public function getLocalStatus(): int {
        return $this->local_status;
    }


    /**
     * @return int
     */
    public function getImageId(): int {
        return $this->imageId;
    }

    /**
     * @return int
     */
    public function getLanguageId(): int {
        return $this->languageId;
    }

    /**
     * @return int
     */
    public function getAuthorId(): int {
        return $this->authorId;
    }
}