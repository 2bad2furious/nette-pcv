<?php


class Setting {
    /** @var int */
    private $id;

    /** @var string */
    private $option;

    /** @var string */
    private $value;
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
     * @return int
     */
    public function getLanguageId(): int {
        return $this->languageId;
    }

    public function isGlobal(): bool {
        return $this->getLanguageId() === 0;
    }
}