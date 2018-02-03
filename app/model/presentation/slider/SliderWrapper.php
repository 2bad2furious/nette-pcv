<?php

/**
 * Class SliderWrapper
 * @method int getId()
 * @method int[] getChildrenIds()
 * @method int getLangId()
 * @method string getTitle()
 */
class SliderWrapper {
    private $language;
    private $languageManager;
    private $slider;
    private $children;
    private $sliderManager;


    public function __construct(ILanguageManager $languageManager, Slider $slider, ISliderManager $sliderManager) {
        $this->languageManager = $languageManager;
        $this->slider = $slider;
        $this->sliderManager = $sliderManager;

        if ($slider->getLangId() === 0) $this->language = false;
    }

    /**
     * @return Slide[]
     */
    public function getChildren(): array {
        if (!is_array($this->children)) {
            $this->children = array_map(function (int $childId) {
                return $this->sliderManager->getSlideById($childId);
            }, $this->getChildrenIds());
        }
        return $this->children;
    }

    /**
     * @return Language|null
     * @throws LanguageByIdNotFound
     */
    public function getLanguage(): ?Language {
        if ($this->language === false) {
            $this->language = $this->languageManager->getById($this->getLangId());
        }
        return $this->language instanceof Language ? $this->language : null;
    }

    /**
     * @return Slider
     */
    public function getSlider(): Slider {
        return $this->slider;
    }


    public function __call($name, $arguments) {
        return call_user_func_array([$this->getSlider(), $name], $arguments);
    }
}