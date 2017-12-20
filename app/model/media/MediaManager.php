<?php


use Nette\Database\Table\ActiveRow;

class MediaManager extends Manager {

    const TABLE = "media",
        COLUMN_ID = "media_id",
        COLUMN_TYPE = "type",
        COLUMN_NAME = "name", COLUMN_NAME_LENGTH = 60,
        COLUMN_SRC = "src", COLUMN_SRC_LENGTH = 255,
        COLUMN_ALT = "alt", COLUMN_ALT_LENGTH = 255;


    const  TYPE_IMAGE = 0, TYPE_PDF = 1, TYPES = [self::TYPE_IMAGE, self::TYPE_PDF];

    /**
     * @param bool $asObjects
     * @return array|Media[]
     */
    public function getAvailableImages($asObjects = false) {
        $available = $this->getDatabase()->table(self::TABLE)->where([
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
            $row[self::COLUMN_NAME],
            $row[self::COLUMN_ALT],
            $row[self::COLUMN_SRC],
            $row[self::COLUMN_TYPE]
        );
    }

    public function getById(int $id):?Media {
        return $this->getCache()->load($id);
    }


    private function getCache(): Cache {
        static $cache = null;
        return $cache instanceof Cache ? $cache : $cache = new Cache($this->getDefaultStorage(), "media");
    }
}