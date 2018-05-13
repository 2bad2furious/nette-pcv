<?php


class Header {
    const TYPE_PAGE = 1,
        TYPE_CUSTOM = 0;

    private $id;
    private $title;
    private $url;
    private $position;
    private $pageId;
    private $languageId;
    private $parentId;
    private $childrenIds = [];


    /**
     * Header constructor.
     * @param int $id
     * @param null|string $title
     * @param null|string $url
     * @param int $position
     * @param int|null $pageId
     * @param int $languageId
     * @param int $parentId
     * @param array $childrenIds
     */
    public function __construct(int $id, ?string $title, ?string $url, int $position, ?int $pageId, int $languageId, ?int $parentId, array $childrenIds) {
        $this->id = $id;
        $this->title = $title;
        $this->url = $url;
        $this->position = $position;
        $this->pageId = $pageId;
        $this->languageId = $languageId;
        $this->parentId = $parentId;

        foreach ($childrenIds as $childrenId) {
            $this->addChildId($childrenId);
        }
    }

    private function addChildId(int $id) {
        $this->childrenIds[] = $id;
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
    public function getTitle():?string {
        return $this->title;
    }

    /**
     * @return null|string
     */
    public function getUrl():?string {
        return $this->url;
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
    public function getPageId():?int {
        return $this->pageId;
    }

    /**
     * @return int
     */
    public function getLanguageId(): int {
        return $this->languageId;
    }

    /**
     * @return int|null
     */
    public function getParentId(): ?int {
        return $this->parentId;
    }

    public function getType(): int {
        return is_int($this->getPageId()) ? self::TYPE_PAGE : self::TYPE_CUSTOM;
    }

    /**
     * @return int[]
     */
    public function getChildrenIds(): array {
        return $this->childrenIds;
    }
}