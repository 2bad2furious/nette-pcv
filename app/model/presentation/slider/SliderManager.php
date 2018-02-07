<?php


use Nette\Database\IRow;

class SliderManager extends Manager implements ISliderManager {

    private const SLIDER_TABLE = "slider",
        SLIDER_COLUMN_ID = "slider_id",
        SLIDER_COLUMN_TITLE = "title", SLIDER_COLUMN_TITLE_LENGTH = 60,
        SLIDER_COLUMN_LANG = "lang_id";

    private const SLIDE_TABLE = "slide",
        SLIDE_COLUMN_ID = "slide_id",
        SLIDE_COLUMN_SLIDER_ID = "slider_id",
        SLIDE_COLUMN_TITLE = "title", SLIDE_COLUMN_TITLE_LENGTH = 60,
        SLIDE_COLUMN_CONTENT = "content",
        SLIDE_COLUMN_POSITION = "position";

    /**
     * @param int $lang_id
     * @param null|string $search
     * @param int $page
     * @param int $perPage
     * @param $numOfPages
     * @return SliderWrapper[]
     */
    public function getAllSliders(int $lang_id = 0, ?string $search = null, int $page, int $perPage, &$numOfPages): array {
        $selection = $this->getDatabase()->table(self::SLIDER_TABLE);
        if (is_int($lang_id)) {
            $selection->where([
                self::SLIDER_COLUMN_LANG => $lang_id,
            ]);
        }
        if (is_string($search)) {
            $selection->whereOr([
                self::SLIDER_TABLE . "." . self::SLIDER_COLUMN_TITLE . " LIKE" => "%" . $search . "%",
            ]);//TODO search in slides?
        }
        $data = $selection->select(self::SLIDER_COLUMN_ID)->page($page, $perPage, $numOfPages);
        $sliders = [];
        while ($row = $data->fetch()) {
            $sliders[$row[self::SLIDER_COLUMN_ID]] = $this->getSliderById($row[self::SLIDER_COLUMN_ID]);
        }
        return $sliders;
    }

    /**
     * @param int $id
     * @param bool $throw
     * @return null|Slide
     * @throws SlideByIdNotFound
     */
    public function getSlideById(int $id, bool $throw = true): ?Slide {
        $slide = $this->getSlideCache()->load($id, function (&$dependencies) use ($id) {
            return $this->getPlainSlideFromDb($id);
        });
        if ($slide instanceof Slide) return $slide;
        if ($throw) throw new SlideByIdNotFound($id);
        return null;
    }

    /**
     * @param int $id
     * @param bool $throw
     * @return null|SliderWrapper
     * @throws SliderByIdNotFound
     */
    public function getSliderById(int $id, bool $throw = true): ?SliderWrapper {
        $slider = $this->getPlainSlider($id, $throw);
        if (!$slider instanceof Slider) return null;

        return new SliderWrapper($this->getLanguageManager(), $slider, $this);
    }

    public function createNewSlider(int $lang_id, string $title): SliderWrapper {
        if (strlen($title) > $this->getSliderTitleMaxLength()) throw new InvalidArgumentException("Slider title is too long. " . $this->getSliderTitleMaxLength() . " characters allowed.");
        if ($lang_id !== 0) $language = $this->getLanguageManager()->getById($lang_id);

        $sliderId = $this->runInTransaction(function () use ($title, $lang_id) {
            $sliderId = $this->getDatabase()->table(self::SLIDER_TABLE)
                ->insert([
                    self::SLIDER_COLUMN_TITLE => $title,
                    self::SLIDER_COLUMN_LANG  => $lang_id,
                ])->getPrimary();

            $this->uncacheSliderId($sliderId);

            return $sliderId;
        });

        return $this->getSliderById($sliderId);
    }

    public function addNewSlide(int $slider_id, string $title, int $position, string $content): Slide {
        // TODO: Implement addNewSlide() method.
    }

    public function editSlider(int $slider_id, int $lang_id, int $title): SliderWrapper {
        // TODO: Implement editSlider() method.
    }

    public function editSlide(int $slide_id, int $position, string $title, string $content): Slide {
        // TODO: Implement editSlide() method.
    }

    public function removeSlide(int $slide_id): SliderWrapper {
        // TODO: Implement removeSlide() method.
    }

    public function deleteSlider(int $slider_id) {
        if (!$this->sliderExists($slider_id)) throw new SliderByIdNotFound($slider_id);
        $this->runInTransaction(function () use ($slider_id) {
            $this->uncacheSliderId($slider_id);
            $this->getDatabase()->table(self::SLIDER_TABLE)
                ->wherePrimary($slider_id)
                ->delete();
        });
    }

    public function moveUp(int $slide_id): SliderWrapper {
        // TODO: Implement moveUp() method.
    }

    public function moveDown(int $slide_id): SliderWrapper {
        // TODO: Implement moveDown() method.
    }

    public function canBeMovedDown(int $slide_id): bool {
        // TODO: Implement canBeMovedDown() method.
    }

    public function canBeMovedUp(int $slide_id): bool {
        // TODO: Implement canBeMovedUp() method.
    }

    public function slideExists(int $slide_id): bool {
        return $this->getSlideById($slide_id, false) instanceof Slide;
    }

    public function sliderExists(int $slider_id): bool {
        return $this->getPlainSlider($slider_id, false) instanceof Slider;
    }

    public function getSliderTitleMaxLength(): int {
        return self::SLIDER_COLUMN_TITLE_LENGTH;
    }

    public function getSlideTitleMaxLength(): int {
        return self::SLIDE_COLUMN_TITLE_LENGTH;
    }

    /**
     * @param int $id
     * @return Slide|false
     */
    private function getPlainSlideFromDb(int $id) {
        $data = $this->getDatabase()->table(self::SLIDE_TABLE)->wherePrimary($id);
        if ($data instanceof IRow) return $this->createNewSlideFromRow($data);
        return false;
    }


    private function createNewSlideFromRow(IRow $row): Slide {
        return new Slide(
            $row[self::SLIDE_COLUMN_ID],
            $row[self::SLIDE_COLUMN_SLIDER_ID],
            $row[self::SLIDE_COLUMN_POSITION],
            $row[self::SLIDE_COLUMN_TITLE],
            $row[self::SLIDE_COLUMN_CONTENT]
        );
    }


    /**
     * @param int $id
     * @param bool $throw
     * @return Slider|null
     * @throws SliderByIdNotFound
     */
    private function getPlainSlider(int $id, bool $throw = true): ?Slider {
        $slider = $this->getSliderCache()->load($id, function (&$dependencies) use ($id) {
            return $this->getPlainSliderFromDb($id);
        });
        if ($slider instanceof Slider) return $slider;

        if ($throw) throw new SliderByIdNotFound($id);
        return null;
    }

    /**
     * @param int $id
     * @return Slider|false
     */
    private function getPlainSliderFromDb(int $id) {
        $data = $this->getDatabase()->table(self::SLIDER_TABLE)
            ->wherePrimary($id)->limit(1)->fetch();

        return $data instanceof \Nette\Database\IRow ? $this->createPlainSliderFromRow($data) : false;
    }

    private function createPlainSliderFromRow(IRow $row) {
        return new Slider(
            $id = $row[self::SLIDER_COLUMN_ID],
            $row[self::SLIDER_COLUMN_TITLE],
            $row[self::SLIDER_COLUMN_LANG],
            $this->getChildrenIds($id)
        );
    }

    private function getChildrenIds(int $id) {
        return array_map(function (IRow $row) {
            return $row[self::SLIDE_COLUMN_ID];
        }, $this->getDatabase()->table(self::SLIDE_TABLE)->where([
            self::SLIDE_COLUMN_SLIDER_ID => $id,
        ])->fetchAll());
    }

    private function getCache(): Cache {
        static $cache = null;
        if (!$cache instanceof Cache) {
            $cache = new Cache($this->getDefaultStorage(), "slider");
        }
        return $cache;
    }

    private function getSliderCache(): Cache {
        static $cache = null;
        if (!$cache instanceof Cache) {
            $cache = $this->getCache()->derive("slider");
        }
        return $cache;
    }

    private function getSlideCache(): Cache {
        static $cache = null;
        if (!$cache instanceof Cache) {
            $cache = $this->getCache()->derive("slide");
        }
        return $cache;
    }

    private function uncacheSliderId(int $sliderId) {
        $this->getSliderCache()->remove($sliderId);
    }
}