<?php


class Page extends APage {
}


class Post extends APage {
}


abstract class APage {
    const CLASS_BY_TYPE = [
        PageManager::TYPE_POST => Post::class,
        PageManager::TYPE_PAGE => Page::class,
    ];

    private $globalId;
    private $localId;
    private $parentId;
    private $langId;
    private $title;
    private $url;
    private $description;
    private $content;
    private $authorId;
    /**
     * @var DateTime
     */
    private $created;
    /**
     * @var DateTime
     */
    private $edited;
    private $imageId;
    private $globalStatus;
    private $localStatus;

    /**
     * APage constructor.
     * @param int $globalId
     * @param int $localId
     * @param int $parentId
     * @param int $langId
     * @param string $title
     * @param string $url
     * @param string $description
     * @param string $content
     * @param int $authorId
     * @param DateTime $created
     * @param DateTime $edited
     * @param int $imageId
     * @param int $globalStatus
     * @param int $localStatus
     */
    public function __construct(int $globalId, int $localId, int $parentId, int $langId, string $title, string $url, string $description, string $content, int $authorId, DateTime $created, DateTime $edited, int $imageId, int $globalStatus, int $localStatus) {
        $this->globalId = $globalId;
        $this->localId = $localId;
        $this->parentId = $parentId;
        $this->langId = $langId;
        $this->title = $title;
        $this->url = $url;
        $this->description = $description;
        $this->content = $content;
        $this->authorId = $authorId;
        $this->created = $created;
        $this->edited = $edited;
        $this->imageId = $imageId;
        $this->globalStatus = $globalStatus;
        $this->localStatus = $localStatus;
    }

    /**
     * @return int
     */
    public function getGlobalId(): int {
        return $this->globalId;
    }

    /**
     * @return int
     */
    public function getLocalId(): int {
        return $this->localId;
    }

    /**
     * @return int
     */
    public function getParentId(): int {
        return $this->parentId;
    }

    /**
     * @return int
     */
    public function getLangId(): int {
        return $this->langId;
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
    public function getUrl(): string {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getDescription(): string {
        return $this->description;
    }

    /**
     * @return string
     */
    public function getContent(): string {
        return $this->content;
    }

    /**
     * @return int
     */
    public function getAuthorId(): int {
        return $this->authorId;
    }

    /**
     * @return DateTime
     */
    public function getCreated(): DateTime {
        return $this->created;
    }

    /**
     * @return DateTime
     */
    public function getEdited(): DateTime {
        return $this->edited;
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
    public function getGlobalStatus(): int {
        return $this->globalStatus;
    }

    /**
     * @return int
     */
    public function getLocalStatus(): int {
        return $this->localStatus;
    }

    public function getStatus(): int {
        return $this->getGlobalStatus() < $this->getLocalStatus() ?
            $this->getGlobalStatus() :
            $this->getLocalStatus();
    }

    public function isPage(): bool {
        return $this instanceof Page;
    }

    public function isPost(): bool {
        return $this instanceof Post;
    }
}