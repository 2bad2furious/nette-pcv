<?php


use Nette\Database\Context;
use Nette\Database\Table\ActiveRow;

class TagManager extends Manager implements ITagManager {

    const MAIN_TABLE = "tag",
        MAIN_COLUMN_ID = "tag_id",

        MAIN_PAGE_TABLE = "tag_page",
        MAIN_PAGE_COLUMN_ID = "tag_page_id",
        MAIN_PAGE_COLUMN_TAG_ID = "tag_id",
        MAIN_PAGE_COLUMN_PAGE_ID = "page_id",

        LOCAL_TABLE = "tag_local",
        LOCAL_COLUMN_ID = "tag_local_id",
        LOCAL_MAIN_COLUMN_ID = "tag_id",
        LOCAL_COLUMN_LANG = "lang_id",
        LOCAL_COLUMN_NAME = "name", LOCAL_COLUMN_NAME_LENGTH = 30;

        const TRIGGER_PAGE_DELETED = "page_deleted";

    /**
     * @param int $pageId
     * @param Language $lang
     * @return array
     */
    public function getTagsForPageId(int $pageId, Language $lang): array {
        $main = $this->getDatabase()->table(self::MAIN_PAGE_TABLE)
            ->select(self::MAIN_PAGE_COLUMN_TAG_ID)
            ->where([self::MAIN_PAGE_COLUMN_PAGE_ID => $pageId])->fetchAll();

        $ids = [];

        foreach ($main as $IRow) {
            $ids[] = $IRow[self::MAIN_PAGE_COLUMN_TAG_ID];
        }

        $locals = $this->getDatabase()->table(self::LOCAL_TABLE)
            ->where([
                self::LOCAL_MAIN_COLUMN_ID => $ids,
            ])->fetchAll();

        $tags = [];

        /** @var ActiveRow $tag */
        foreach ($locals as $tag) {
            $tags[] = $this->createFromRow($tag, $lang);
        }

        return $tags;
    }

    private function createFromRow(ActiveRow $row, Language $language): Tag {
        return new Tag(
            $row[self::LOCAL_MAIN_COLUMN_ID],
            $row[self::LOCAL_COLUMN_NAME],
            $language
        );
    }

}