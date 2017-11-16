<?php


class Media {
    private $id;
    private $language;
    private $name;
    private $alt;
    private $src;
    private $type;
    private $languageId;


    /**
     * Media constructor.
     * @param int $id
     * @param int $languageId
     * @param string $name
     * @param string $alt
     * @param string $src
     * @param int $type
     */
    public function __construct(int $id, int $languageId, string $name, string $alt, string $src, int $type) {
        $this->id = $id;
        $this->languageId = $languageId;
        $this->name = $name;
        $this->alt = $alt;
        $this->src = $src;
        $this->type = $type;
    }

    /**
     * @param Language $language
     * @throws Exception
     */
    public function setLanguage(Language $language) {
        if($this->language instanceof Language) throw new Exception("Language already set");
        if($this->getLanguageId() !== $language->getId()) throw new Exception("Ids are not the same");
        $this->language = $language;
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @return Language|null
     */
    public function getLanguage():?Language {
        return $this->language;
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