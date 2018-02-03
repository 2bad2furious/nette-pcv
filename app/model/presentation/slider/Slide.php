<?php


class Slide {
    private $id;
    private $slider_id;
    private $position;
    private $title;
    private $content;

    public function __construct(int $id, int $slider_id, int $position, string $title, string $content) {
        $this->id = $id;
        $this->slider_id = $slider_id;
        $this->position = $position;
        $this->title = $title;
        $this->content = $content;
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
    public function getSliderId(): int {
        return $this->slider_id;
    }

    /**
     * @return int
     */
    public function getPosition(): int {
        return $this->position;
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
    public function getContent(): string {
        return $this->content;
    }

}