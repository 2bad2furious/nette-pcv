<?php


interface ISliderManager extends IManager {
    public function getSlideById(int $id, bool $throw = true): ?Slide;

    public function getSliderById(int $id, int $langId = 0, bool $throw = true): ?SliderWrapper;

    public function createNew(): SliderWrapper;

    public function editSlide(int $slide_id, int $position, string $title, string $content): Slide;

    public function editSlider(int $slider_id, int $title): SliderWrapper;

    public function removeSlide(int $slide_id): SliderWrapper;

    public function deleteSlider(int $slider_id);

    public function moveUp(int $slide_id): SliderWrapper;

    public function moveDown(int $slide_id): SliderWrapper;

    public function canBeMovedDown(int $slide_id): bool;

    public function canBeMovedUp(int $slide_id): bool;

    public function slideExists(int $slide_id): bool;

    public function sliderExists(int $slider_id): bool;
}