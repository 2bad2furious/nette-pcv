<?php


class Tag {
    /** @var  int */
    private $id;
    /** @var  string */
    private $name;
    /** @var  Language */
    private $language;

    /**
     * Tag constructor.
     * @param int $id
     * @param string $name
     * @param Language $language
     */
    public function __construct(int $id,string $name, Language $language) {
        $this->id = $id;
        $this->name = $name;
        $this->language = $language;
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
     * @return Language
     */
    public function getLanguage(): Language {
        return $this->language;
    }
}