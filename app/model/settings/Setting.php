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
     * Setting constructor.
     * @param int $id
     * @param Language|null $language
     * @param string $option
     * @param string $value
     */
    public function __construct(int $id, ?Language $language, string $option, string $value) {
        $this->id = $id;
        $this->option = $option;
        $this->value = $value;
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
}