<?php


use Nette\Database\Table\IRow;

class HeaderManager extends Manager implements IHeaderManager {
    const TABLE = "header",
        COLUMN_ID = "header_id",
        COLUMN_LANG = "lang",
        COLUMN_PAGE_ID = "page_id",
        COLUMN_PAGE_URL = "url",
        COLUMN_TITLE = "title",
        COLUMN_POSITION = "position",
        COLUMN_PARENT_ID = "parent_id";


    public function cleanCache() {
        $this->getCache()->clean();

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
        ]);


        /** @var IRow $row */
        while ($row = $rows->fetch()) {
            $headerPage = $this->getFromRow($row);

            dump($headerPage, $row);

            $this->addChildren($headerPage, $language);
            $header->addChild($headerPage);
        }
    }

    /**
     * @param Language $language
     * @param null|Page $currentPage
     * @return HeaderPage
     */
    public function getRoot(Language $language, ?Page $currentPage): HeaderPage {

        $root = $this->getCache()->load($language->getId(), function () use ($language) {
            return $this->getHeaderFromDb($language);
        });

        $this->setPagesOnChildren($root, $currentPage, $language);

        return $root;
    }

    private function setPagesOnChildren(HeaderPage &$parent, ?Page $currentPage, Language $language): bool {
        $isActive = false;
        foreach ($parent->getChildren() as $child) {
            if ($isChildActive = $this->setPagesOnChildren($child, $currentPage, $language)) $isActive = $isChildActive;
            $pageId = $child->getPageId();

            $page = (is_int($pageId)) ? $this->getPageManager()->getByGlobalId($language, $pageId) : null;

            if (is_int($pageId) && !$page instanceof Page) throw new InvalidState("Page $pageId for child {$child->getHeaderPageId()} not found");

            $language = $this->getLanguageManager()->getById($langId = $child->getLanguageId());
            if (!$language instanceof Language) throw new InvalidState("Language {$langId} not found");

            $child->setLanguage($language);

            if ($page instanceof Page) $child->setPage($page);

            $currentPageActive = $currentPage instanceof Page ? $child->getUrl() === $currentPage->getCompleteUrl() : false;
            if ($currentPageActive) $isActive = $currentPageActive;

            $child->setActive($isActive);
        }
        return $isActive;
    }

    private function getCache(): Cache {
        static $cache = null;
        return $cache instanceof Cache ? $cache : $cache = new Cache($this->getDefaultStorage(), "header");
    }

    /**
     * @param Language $language
     * @return HeaderPage
     */
    private function getHeaderFromDb(Language $language) {
        $headerPage = new HeaderPage(0, null, $language->getId(), null, null);
        $this->addChildren($headerPage, $language);
        return $headerPage;
    }

    private function getFromRow(IRow $row) {
        return new HeaderPage(
            $row[self::COLUMN_ID],
            $row[self::COLUMN_PAGE_ID],
            $row[self::COLUMN_LANG],
            ($row[self::COLUMN_PAGE_ID] === null) ? $row[self::COLUMN_PAGE_URL] : null,
            ($row[self::COLUMN_PAGE_ID] === null) ? $row[self::COLUMN_TITLE] : null
        );
    }

    public function getNextId(): int {
        return $this->getDatabase()->table(self::TABLE)
                ->aggregation("MAX(" . self::COLUMN_ID . ")")
            + 1;
    }
}