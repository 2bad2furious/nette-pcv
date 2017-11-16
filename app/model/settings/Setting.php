<?php


class Setting {
    /** @var int */
    private $id;

    /** @var string */
    private $option;

    /** @var string */
    private $value;
    /**
     * @var Language|null
     */
    private $language;
    /**
     * @var int
     */
    private $languageId;

    /**
     * Setting constructor.
     * @param int $id
     * @param int $languageId
     * @param string $option
     * @param string $value
     */
    public function __construct(int $id, int $languageId, string $option, string $value) {
        $this->id = $id;
        $this->option = $option;
        $this->value = $value;
        $this->languageId = $languageId;
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
    public function getOption(): string {
        return $this->option;
    }

    /**
     * @return string
     */
    public function getValue(): string {
        return $this->value;
    }

    /**
     * @return Language|null
     */
    public function getLanguage(): ?Language {
        return $this->language;
    }

    /**
     * @param Language $language
     * @throws Exception
     */
    public function setLanguage(Language $language) {
        if ($this->language instanceof Language) throw new Exception("Language already set");
        if ($this->languageId !== $language->getId()) throw new Exception("Ids are not the same");
        $this->language = $language;
    }

    /**
     * @return int
     */
    public function getLanguageId(): int {
        return $this->languageId;
    }
}