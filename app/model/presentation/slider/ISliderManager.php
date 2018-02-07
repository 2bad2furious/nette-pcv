<?php


interface ISliderManager extends IManager {
    /**
     * @param int $lang_id
     * @param null|string $search
     * @param int $page
     * @param int $perPage
     * @param $numOfPages
     * @return SliderWrapper[]
     */
    public function getAllSliders(int $lang_id = 0,?string $search = null,int $page,int $perPage, &$numOfPages): array;

    public function getSlideById(int $id, bool $throw = true): ?Slide;

    public function getSliderById(int $id, bool $throw = true): ?SliderWrapper;

    public function createNewSlider(int $lang_id, string $title): SliderWrapper;

    public function addNewSlide(int $slider_id, string $title, int $position, string $content): Slide;

    public function editSlider(int $slider_id, int $lang_id, int $title): SliderWrapper;

    public function editSlide(int $slide_id, int $position, string $title, string $content): Slide;

    public function removeSlide(int $slide_id): SliderWrapper;

    public function deleteSlider(int $slider_id);

    public function moveUp(int $slide_id): SliderWrapper;

    public function moveDown(int $slide_id): SliderWrapper;

    public function canBeMovedDown(int $slide_id): bool;

    public function canBeMovedUp(int $slide_id): bool;

    public function slideExists(int $slide_id): bool;

    public function sliderExists(int $slider_id): bool;

    public function getSliderTitleMaxLength(): int;

    public function getSlideTitleMaxLength(): int;
}