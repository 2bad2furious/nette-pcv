<?php


class Media {
    private $id;
    private $language;
    private $name;
    private $alt;
    private $src;
    private $type;


    /**
     * Media constructor.
     * @param int $id
     * @param string $name
     * @param string $alt
     * @param string $src
     * @param int $type
     */
    public function __construct(int $id, string $name, string $alt, string $src, int $type) {
        $this->id = $id;
        $this->name = $name;
        $this->alt = $alt;
        $this->src = $src;
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getAlt(): string {
        return $this->alt;
    }

    /**
     * @return string
     */
    public function getSrc(): string {
        return $this->src;
    }

    /**
     * @return int
     */
    public function getType(): int {
        return $this->type;
    }

    public function isImage(): bool {
        return $this->getType() === MediaManager::TYPE_IMAGE;
    }

    public function getLanguageId():int {
        return $this->languageId;
    }
}