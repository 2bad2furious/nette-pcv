<?php


use Nette\Database\Table\ActiveRow;

class HeaderManager extends Manager {
    const TABLE = "header",
        COLUMN_ID = "header_id",
        COLUMN_LANG = "lang",
        COLUMN_PAGE_ID = "page_id",
        COLUMN_PAGE_URL = "url",
        COLUMN_TITLE = "title",
        COLUMN_POSITION = "position",
        COLUMN_PARENT_ID = "parent_id";


    protected function init() {
        //TODO register listeners
    }


    /**
     * TODO optimize this
     */
    public function rebuildCache() {
        //TODO check rights
        //clean cache
        $this->getCache()->clean();

        $languages = $this->getDatabase()->table(self::TABLE)->select(self::COLUMN_LANG)->group(self::COLUMN_LANG)->fetchAll();
        /** @var ActiveRow $langRow */
        foreach ($languages as $langRow) {
            $language = $this->getLanguageManager()->getById($langRow[self::COLUMN_LANG]);
            $root = new HeaderPage(0, 0, $language, false);
            $this->addChildren($root, $language);

            $this->getCache()->save($language->getId(), $root);
        }
    }

    /**
     * @param HeaderPage $header
     * @param Language $language
     * @throws Exception
     */
    private function addChildren(HeaderPage &$header, Language $language): void {
        $rows = $this->getDatabase()->table(self::TABLE)->where([
            self::COLUMN_LANG      => $language->getId(),
            self::COLUMN_PARENT_ID => $header->getHeaderPageId(),
        ])->fetchAll();

        /** @var ActiveRow $row */
        foreach ($rows as $row) {
            $headerPage = new HeaderPage(
                $row[self::COLUMN_ID],
                $row[self::COLUMN_PAGE_ID],
                $language,
                ($row[self::COLUMN_PAGE_ID] === null) ? $row[self::COLUMN_PAGE_URL] : null,
                ($row[self::COLUMN_PAGE_ID] === null) ? $row[self::COLUMN_TITLE] : null
            );

            $this->addChildren($headerPage, $language);
            $header->addChild($headerPage);
        }
    }

    /**
     * @param Language $language
     * @param null|Page $currentPage
     * @return HeaderPage|null
     */
    public function getRoot(Language $language, ?Page $currentPage): ?HeaderPage {
        /** @var HeaderPage|null $root */
        $root = $this->getCache()->load($language->getId());
        if ($root instanceof HeaderPage) $this->setPagesOnChildren($root, $currentPage, $language);
        else return new HeaderPage(0, -1, $language);
        return $root;
    }

    private function setPagesOnChildren(HeaderPage $parent, ?Page $currentPage, Language $language) {
        foreach ($parent->getChildren() as $child) {
            $this->setPagesOnChildren($child, $currentPage, $language);
            $pageId = $child->getPageId();
            $page = (is_int($pageId)) ? $this->getPageManager()->getByGlobalId($language, $pageId) : null;
            if ($page instanceof Page) $child->setPage($page);
            $child->setActive($currentPage instanceof Page ? $child->getUrl() === $currentPage->getCompleteUrl() : false);
        }
    }

    private function getCache():Cache{
        static $cache = null;
        return $cache instanceof Cache ? $cache : $cache = new Cache($this->getDefaultStorage(), "header");
    }
}