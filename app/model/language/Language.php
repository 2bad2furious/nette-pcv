<?php


class Language {
    /** @var int */
    private $id;

    /** @var string */
    private $code;

    /**
     * Language constructor.
     * @param int $id
     * @param string $code
     */
    public function __construct(int $id, string $code) {
        $this->id = $id;
        $this->code = $code;
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
    public function getCode(): string {
        return $this->code;
    }
}