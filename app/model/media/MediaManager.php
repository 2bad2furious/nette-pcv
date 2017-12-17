<?php


use Nette\Database\Table\ActiveRow;

class MediaManager{
    use ManagerUtils;

    const TABLE = "media",
        COLUMN_ID = "media_id",
        COLUMN_TYPE = "type",
        COLUMN_LANG = "lang_id",
        COLUMN_NAME = "name", COLUMN_NAME_LENGTH = 60,
        COLUMN_SRC = "src", COLUMN_SRC_LENGTH = 255,
        COLUMN_ALT = "alt", COLUMN_ALT_LENGTH = 255;


    const  TYPE_IMAGE = 0, TYPE_PDF = 1, TYPES = [self::TYPE_IMAGE, self::TYPE_PDF];

    /**
     * @param Language|null $language
     * @param bool $asObjects
     * @return array|Media[]
     */
    public function getAvailableImages(?Language $language = null, $asObjects = false) {
        $languages = $language instanceof Language ? [0, $language->getId()] : 0;
        $available = $this->getDatabase()->table(self::TABLE)->where([
            self::COLUMN_LANG => $languages,
            self::COLUMN_TYPE => self::TYPE_IMAGE,
        ])->fetchAll();
        $availableImages = [];
        foreach ($available as $image) {
            $availableImages[$image[self::COLUMN_ID]] = ($asObjects) ? $this->getById($image[self::COLUMN_ID]) : $image[self::COLUMN_NAME];
        }
        return $availableImages;
    }


    private function createFromDbRow(ActiveRow $row): Media {
        return new Media(
            $row[self::COLUMN_ID],
            $row[self::COLUMN_LANG],
            $row[self::COLUMN_NAME],
            $row[self::COLUMN_ALT],
            $row[self::COLUMN_SRC],
            $row[self::COLUMN_TYPE]
        );
    }

    public function getById(int $id):?Media {
        $media = $this->getCache()->load($id);
        if ($media instanceof Media) {
            if ($langId = $media->getLanguageId() !== 0)
                $media->setLanguage($this->getLanguageManager()->getById($langId));
        }
        return $media;
    }


    private function getCache(): Cache {
        static $cache = null;
        return $cache instanceof Cache ? $cache : $cache = new Cache($this->getDefaultStorage(), "media");
    }

    protected function init() {
        // TODO: Implement init() method.
    }
}