<?php

use Nette\Utils\DateTime;

abstract class APage {
    const CLASS_BY_TYPE = [
        PageManager::TYPE_POST => Post::class,
        PageManager::TYPE_PAGE => Page::class,
    ];


    /**
     * @param int $type
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
     * @param bool $displayTitle
     * @param bool $displayBreadcrumbs
     * @param array $childrenIds
     * @return APage
     */
    public static function factory(int $type, int $globalId, int $localId, ?int $parentId, int $langId, string $title, string $url, string $description, string $content, int $authorId, DateTime $created, DateTime $edited, int $imageId, int $globalStatus, int $localStatus, bool $displayTitle, bool $displayBreadcrumbs, array $childrenIds): APage {
        $className = APage::CLASS_BY_TYPE[$type];

        return new $className($globalId, $localId, $parentId, $langId, $title, $url, $description, $content, $authorId, $created, $edited, $imageId, $globalStatus, $localStatus, $displayTitle, $displayBreadcrumbs, $childrenIds);
    }

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
    private $displayTitle;
    private $displayBreadcrumbs;
    private $childrenIds = [];

    /**
     * APage constructor.
     * @param int $globalId
     * @param int $localId
     * @param int|null $parentId
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
     * @param bool $displayTitle
     * @param bool $displayBreadcrumbs
     * @param int[] $childrenIds
     */
    public function __construct(int $globalId, int $localId, ?int $parentId, int $langId, string $title, string $url, string $description, string $content, int $authorId, DateTime $created, DateTime $edited, int $imageId, int $globalStatus, int $localStatus, bool $displayTitle, bool $displayBreadcrumbs, array $childrenIds) {
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
        $this->displayTitle = $displayTitle;
        $this->displayBreadcrumbs = $displayBreadcrumbs;

        foreach ($childrenIds as $childrenId) {
            $this->addChild($childrenId);
        }
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
     * @return int|null
     */
    public function getParentId(): ?int {
        dump($this->parentId);
        return $this->parentId;
    }

    /**
     * @return int
     */
    public function getLanguageId(): int {
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
        return min($this->getGlobalStatus(), $this->getLocalStatus());
    }

    public function isPage(): bool {
        return $this instanceof Page;
    }

    public function isPost(): bool {
        return $this instanceof Post;
    }

    private function addChild(int $childrenId) {
        $this->childrenIds[] = $childrenId;
    }

    /**
     * @return int[]
     */
    public function getChildrenIds(): array {
        return $this->childrenIds;
    }

    public abstract function getOgType(): string;

    public abstract function getType(): string;

    /**
     * @return bool
     */
    public function getDisplayTitle(): bool {
        return $this->displayTitle;
    }

    /**
     * @return bool
     */
    public function getDisplayBreadCrumbs(): bool {
        return $this->displayBreadcrumbs;
    }
}

class Page extends APage {

    public function getOgType(): string {
        return "website";
    }

    public function getType(): string {
        return "page";
    }
}

class Post extends APage {
    public function getOgType(): string {
        return "article";
    }

    public function getType(): string {
        return "post";
    }
}
