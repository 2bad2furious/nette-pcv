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

        $root = $this->getCache()->load($language->getCode(), function () use ($language) {
            return $this->getHeaderFromDb($language);
        });

        $this->setPagesOnChildren($root, $currentPage, $language);

        return $root;
    }

    /**
     * @param int $id
     * @return HeaderPage|null
     */
    public function getById(int $id):?HeaderPage {
        $data = $this->getDatabase()->table(self::TABLE)
            ->wherePrimary($id)->fetch();

        return ($data instanceof IRow) ? $this->getFromRow($data) : null;
    }

    public function addPage(HeaderPage $parent, int $pageId, string $title): int {
        $langId = $parent->getLanguageId();

        $language = $this->getLanguageManager()->getById($langId);
        if (!$language instanceof Language) throw new InvalidState("Language not found");

        $page = $this->getPageManager()->getByGlobalId($language, $pageId);
        if (!$page instanceof Page) throw new InvalidArgumentException("Page $pageId not found");

        $inTransaction = $this->getDatabase()->getConnection()->getPdo()->inTransaction();
        if (!$inTransaction) $this->getDatabase()->beginTransaction();
        try {
            $this->uncache($language->getCode());

            $id = $this->getDatabase()->table(self::TABLE)
                ->insert([
                    self::COLUMN_PAGE_ID   => $pageId,
                    self::COLUMN_TITLE     => $title,
                    self::COLUMN_PARENT_ID => $parent->getHeaderPageId(),
                    self::COLUMN_LANG      => $langId,
                ])->getPrimary();

            if (!$inTransaction) $this->getDatabase()->commit();
        } catch (Exception $ex) {
            if (!$inTransaction) $this->getDatabase()->rollBack();
            throw $ex;
        }
        return $id;
    }

    public function addCustom(HeaderPage $parent, string $title, string $url): int {

        if (!filter_var($url, FILTER_VALIDATE_URL)) throw new InvalidArgumentException("Url not valid");

        $langId = $parent->getLanguageId();

        $language = $this->getLanguageManager()->getById($langId);
        if (!$language instanceof Language) throw new InvalidState("Language not found");

        $inTransaction = $this->getDatabase()->getConnection()->getPdo()->inTransaction();
        if (!$inTransaction) $this->getDatabase()->beginTransaction();
        try {
            $this->uncache($language->getCode());

            $id = $this->getDatabase()->table(self::TABLE)
                ->insert([
                    self::COLUMN_PAGE_URL  => $url,
                    self::COLUMN_TITLE     => $title,
                    self::COLUMN_PARENT_ID => $parent->getHeaderPageId(),
                    self::COLUMN_LANG      => $langId,
                ])->getPrimary();

            if (!$inTransaction) $this->getDatabase()->commit();
        } catch (Exception $ex) {
            if (!$inTransaction) $this->getDatabase()->rollBack();
            throw $ex;
        }
        return $id;
    }

    public function editPage(HeaderPage $headerPage, int $pageId, string $title) {
        $langId = $headerPage->getLanguageId();

        $language = $this->getLanguageManager()->getById($langId);
        if (!$language instanceof Language) throw new InvalidState("Language not found");

        $page = $this->getPageManager()->getByGlobalId($language, $pageId);
        if (!$page instanceof Page) throw new InvalidArgumentException("Page $pageId not found");

        $inTransaction = $this->getDatabase()->getConnection()->getPdo()->inTransaction();
        if (!$inTransaction) $this->getDatabase()->beginTransaction();
        try {
            $this->uncache($language->getCode());

            $this->getDatabase()->table(self::TABLE)
                ->wherePrimary($headerPage->getHeaderPageId())
                ->update([
                    self::COLUMN_PAGE_ID => $pageId,
                    self::COLUMN_TITLE   => $title,
                ]);

            if (!$inTransaction) $this->getDatabase()->commit();
        } catch (Exception $ex) {
            if (!$inTransaction) $this->getDatabase()->rollBack();
            throw $ex;
        }
    }

    public function editCustom(HeaderPage $headerPage, string $title, string $url) {
        if (!filter_var($url, FILTER_VALIDATE_URL)) throw new InvalidArgumentException("Url not valid");

        $langId = $headerPage->getLanguageId();

        $language = $this->getLanguageManager()->getById($langId);
        if (!$language instanceof Language) throw new InvalidState("Language not found");

        $inTransaction = $this->getDatabase()->getConnection()->getPdo()->inTransaction();
        if (!$inTransaction) $this->getDatabase()->beginTransaction();
        try {
            $this->uncache($language->getCode());

            $this->getDatabase()->table(self::TABLE)
                ->wherePrimary($headerPage->getHeaderPageId())
                ->update([
                    self::COLUMN_PAGE_ID  => null,
                    self::COLUMN_TITLE    => $title,
                    self::COLUMN_PAGE_URL => $url,
                ]);

            if (!$inTransaction) $this->getDatabase()->commit();
        } catch (Exception $ex) {
            if (!$inTransaction) $this->getDatabase()->rollBack();
            throw $ex;
        }
    }

    /**
     * @param int $id
     * @return void
     * @throws InvalidArgumentException|Exception
     */
    public function delete(int $id) {
        $headerPage = $this->getById($id);
        if (!$headerPage instanceof HeaderPage) throw new InvalidArgumentException("Header page $id not found");

        $langId = $headerPage->getLanguageId();
        $language = $this->getLanguageManager()->getByCode($langId);
        if (!$language instanceof Language) throw new InvalidArgumentException("Language $langId not found");

        $this->uncache($language->getCode());

        $inTransaction = $this->getDatabase()->getConnection()->getPdo()->inTransaction();
        if (!$inTransaction) $this->getDatabase()->beginTransaction();
        try {

            $this->getDatabase()->table(self::TABLE)
                ->wherePrimary($headerPage->getHeaderPageId())
                ->delete();

            if (!$inTransaction) $this->getDatabase()->commit();
        } catch (Exception $exception) {
            if (!$inTransaction) $this->getDatabase()->rollBack();
            throw $exception;
        }
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
        $headerPage = new HeaderPage(0, null, $language->getId(), null, null, 0);
        $this->addChildren($headerPage, $language);
        return $headerPage;
    }

    private function getFromRow(IRow $row) {
        return new HeaderPage(
            $row[self::COLUMN_ID],
            $row[self::COLUMN_PAGE_ID],
            $row[self::COLUMN_LANG],
            ($row[self::COLUMN_PAGE_ID] === null) ? $row[self::COLUMN_PAGE_URL] : null,
            ($row[self::COLUMN_PAGE_ID] === null) ? $row[self::COLUMN_TITLE] : null,
            $row[self::COLUMN_POSITION]
        );
    }

    private function uncache(int $langId) {
        $this->getCache()->remove($langId);
    }
}