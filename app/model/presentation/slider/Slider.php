<?php


class Slider {
    private $id;
    private $lang_id;
    private $title;
    private $childrenIds = [];

    /**
     * Slider constructor.
     * @param int $id
     * @param string $title
     * @param int $lang_id
     * @param int[] $childrenIds
     */
    public function __construct(int $id, string $title, int $lang_id, array $childrenIds) {
        $this->id = $id;
        $this->title = $title;
        foreach ($childrenIds as $childrenId) {
            $this->addChildId($childrenId);
        }
        $this->lang_id = $lang_id;
    }

    private function addChildId(int $childrenId) {
        $this->childrenIds[$childrenId] = $childrenId;
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getLangId(): int {
        return $this->lang_id;
    }

    /**
     * @return string
     */
    public function getTitle(): string {
        return $this->title;
    }

    /**
     * @return int[]
     */
    public function getChildrenIds(): array {
        return $this->childrenIds;
    }
}