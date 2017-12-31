<?php


use Nette\Database\IRow;

class HeaderManager extends Manager implements IHeaderManager {

    const TABLE = "header",
        COLUMN_ID = "header_id",
        COLUMN_LANG = "lang",
        COLUMN_PAGE_ID = "page_id",
        COLUMN_PAGE_URL = "url",
        COLUMN_TITLE = "title",
        COLUMN_POSITION = "position",
        COLUMN_PARENT_ID = "parent_id";

    private $currentPage;

    public function cleanCache() {
        $this->getCache()->clean();
    }

    /**
     * @param Language $language
     * @param null|Page $currentPage for detection of active branch
     * @return array
     */
    public function getHeader(Language $language, ?Page $currentPage = null): array {
        $this->currentPage = $currentPage;

        $header = $this->getRootChildren($language);

        $this->currentPage = null;

        return $header;
    }

    /**
     * @param int $id
     * @return HeaderWrapper|null
     */
    public function getById(int $id):?HeaderWrapper {
        $cached = $this->getCache()->load($id, function (&$dependencies) use ($id) {
            //TODO set TAGS such as lang, page_id
            return $this->getFromDb($id);
        });

        if ($cached instanceof Header) {
            return $this->constructHeaderWrapper($cached);
        }
        return null;
    }

    private function getChildren(Header $header, bool &$active): array {
        return array_map(function (int $headerId) use ($active) {
            $child = $this->getById($headerId);
            if ($child->isActive() ||
                ($this->currentPage instanceof Page &&
                    $this->currentPage->getGlobalId() == $child->getPageId()
                )
            ) $active = true;

            return $child;
        }, $header->getChildrenIds());
    }

    public function addPage(int $parentId, int $languageId, int $pageId, string $title): HeaderWrapper {
        if ($parentId !== 0 && !$this->exists($parentId, $languageId)) throw new InvalidArgumentException("Parent {$parentId} does not exist");

        $language = $this->getLanguageManager()->getById($languageId);
        if (!$language instanceof Language) throw new InvalidArgumentException("Language {$languageId} does not exist");

        $page = $this->getPageManager()->getByGlobalId($language, $pageId);
        if (!$page instanceof Page) throw new InvalidArgumentException("Page {$pageId} does not exist");

        return $this->runInTransaction(function () use ($title, $pageId, $languageId, $parentId) {
            $headerId = $this->getDatabase()->table(self::TABLE)
                ->insert([
                    self::COLUMN_TITLE     => $title ?: null,
                    self::COLUMN_LANG      => $languageId,
                    self::COLUMN_PARENT_ID => $parentId,
                    self::COLUMN_PAGE_ID   => $pageId,
                ])->getPrimary();

            $this->uncache($headerId);
            $this->uncache($parentId);

            $header = $this->getById($headerId);

            $this->adjustPositionsAround($header);

            return $header;
        });

    }

    public function addCustom(int $parentId, int $languageId, string $title, string $url): HeaderWrapper {
        if ($parentId !== 0 && !$this->exists($parentId, $languageId)) throw new InvalidArgumentException("Parent {$parentId} does not exist");

        $language = $this->getLanguageManager()->getById($languageId);
        if (!$language instanceof Language) throw new InvalidArgumentException("Language {$languageId} does not exist");

        if (!filter_var($url, FILTER_VALIDATE_URL)) throw new InvalidArgumentException("Url {$url} is not valid");

        return $this->runInTransaction(function () use ($url, $title, $languageId, $parentId) {
            $headerId = $this->getDatabase()->table(self::TABLE)
                ->insert([
                    self::COLUMN_TITLE     => $title ?: null,
                    self::COLUMN_LANG      => $languageId,
                    self::COLUMN_PARENT_ID => $parentId,
                    self::COLUMN_PAGE_URL  => $url,
                ])->getPrimary();

            $this->uncache($headerId);
            $this->uncache($parentId);


            $header = $this->getById($headerId);;

            $this->adjustPositionsAround($header);

            return $header;
        });
    }

    public function editPage(int $headerId, int $pageId, string $title) {
        $header = $this->getById($headerId);
        if (!$header instanceof HeaderWrapper) throw new InvalidArgumentException("Header {$headerId} does not exist");

        if (!$this->getPageManager()->getByGlobalId($header->getLanguage(), $pageId))
            throw new InvalidArgumentException("Page {$pageId} does not exist");

        $this->uncache($headerId);

        $this->runInTransaction(function () use ($headerId, $pageId, $title) {
            return $this->getDatabase()->table(self::TABLE)
                ->wherePrimary($headerId)
                ->update([
                    self::COLUMN_PAGE_ID => $pageId,
                    self::COLUMN_TITLE   => $title ?: null,
                ]);
        });
    }

    public function editCustom(int $headerId, string $title, string $url) {
        if (!$this->exists($headerId)) throw new InvalidArgumentException("Header {$headerId} does not exist");

        if (!filter_var($url, FILTER_VALIDATE_URL)) throw new InvalidArgumentException("Url {$url} is not valid");

        $this->uncache($headerId);

        $this->runInTransaction(function () use ($headerId, $title, $url) {
            return $this->getDatabase()->table(self::TABLE)
                ->wherePrimary($headerId)
                ->update([
                    self::COLUMN_TITLE    => $title,
                    self::COLUMN_PAGE_URL => $url,
                ]);
        });
    }

    /**
     * @param int $id
     * @return void
     * @throws InvalidArgumentException
     */
    public function delete(int $id) {
        $headerWrapper = $this->getById($id);
        if (!$headerWrapper instanceof HeaderWrapper) throw new InvalidArgumentException("Header {$id} not found");

        $this->uncache($id);

        $children = $this->getDatabase()->table(self::TABLE)->where([self::COLUMN_PARENT_ID => $id])->fetch();
        if ($children) throw new InvalidArgumentException("Header {$id} still has children");

        $this->runInTransaction(function () use ($id) {
            return $this->getDatabase()->table(self::TABLE)->wherePrimary($id)->delete();
        });

        $this->adjustPositionsAround($headerWrapper);
    }

    public function deleteBranch(int $id) {
        $headerWrapper = $this->getById($id);
        if (!$headerWrapper instanceof HeaderWrapper) throw new InvalidArgumentException("Header {$id} not found");

        $this->uncache($id);

        $this->runInTransaction(function () use ($id) {
            $children = $this->getDatabase()->table(self::TABLE)->where([self::COLUMN_PARENT_ID => $id])->fetchAll();

            foreach ($children as $child) {
                $this->deleteBranch($child[self::COLUMN_ID]);
            }

            $this->delete($id);
        });

        $this->adjustPositionsAround($headerWrapper);
    }

    private function getCache(): Cache {
        static $cache = null;
        return $cache instanceof Cache ? $cache : $cache = new Cache($this->getDefaultStorage(), "header");
    }

    /**
     * @param int $id
     * @return Header|false
     */
    private function getFromDb(int $id) {
        $data = $this->getDatabase()->table(self::TABLE)
            ->wherePrimary($id)->fetch();

        if (!$data instanceof IRow) return false;

        $childrenIds = $this->getChildrenIds($data[self::COLUMN_PARENT_ID]);

        return new Header(
            $data[self::COLUMN_ID],
            $data[self::COLUMN_TITLE],
            $data[self::COLUMN_PAGE_URL],
            $data[self::COLUMN_POSITION],
            $data[self::COLUMN_PAGE_ID],
            $data[self::COLUMN_LANG],
            $data[self::COLUMN_PARENT_ID],
            $childrenIds
        );
    }


    /**
     * @param Language $language
     * @return HeaderWrapper[]
     */
    private function getRootChildren(Language $language) {
        return array_map(function (int $id) {
            return $this->getById($id);
        }, $this->getRootChildrenIds($language->getId()));
    }

    private function getRootChildrenIds(int $langId): array {
        $data = $this->getDatabase()->table(self::TABLE)
            ->where([
                self::COLUMN_PARENT_ID => 0,
                self::COLUMN_LANG      => $langId,
            ])->select(self::COLUMN_ID)
            ->order(self::COLUMN_POSITION)
            ->fetchAll();

        return array_map(function (IRow $row) {
            return $row[self::COLUMN_ID];
        }, $data);
    }

    private function constructHeaderWrapper(Header $header): HeaderWrapper {
        $pageId = $header->getPageId();
        $langId = $header->getLanguageId();


        $active = false;
        return new HeaderWrapper(
            $header,
            $language = $this->getLanguageManager()->getById($langId),
            is_int($pageId) ? $this->getPageManager()->getByGlobalId($language, $pageId) : null,
            $this->getChildren($header, $active),
            $active
        );
    }

    public function exists(int $id, ?int $langId = null): bool {
        $where = [self::COLUMN_ID => $id];
        if (is_int($langId)) $where[self::COLUMN_LANG] = $langId;

        return is_int($this->getDatabase()->table(self::TABLE)
            ->where($where)
            ->fetchField(self::COLUMN_ID));
    }

    private function uncache($key) {
        $this->getCache()->remove($key);
    }

    public function changeParentOrPosition(int $headerId, int $parentHeaderId, int $position) {
        if ($headerId === $parentHeaderId) throw new InvalidArgumentException("$headerId: Ids are the same");

        $headerWrapper = $this->getById($headerId);
        if (!$headerWrapper instanceof HeaderWrapper) throw new InvalidArgumentException("Header $headerId not found");

        if ($parentHeaderId !== 0 && !$this->exists($parentHeaderId)) throw new InvalidArgumentException("Parent not found");

        if ($parentHeaderId !== 0 && !$this->exists($parentHeaderId, $headerWrapper->getLanguageId())) throw new InvalidArgumentException("Parent is not of the same language");

        if (!$this->canChangeParent($headerId, $parentHeaderId)) throw new InvalidArgumentException("New parent $parentHeaderId is already a descendant of $headerId");

        $this->uncache($headerId);
        $this->uncache($parentHeaderId);
        $this->uncache($headerWrapper->getParentId());

        $this->runInTransaction(function () use ($headerId, $parentHeaderId, $position) {
            return $this->getDatabase()->table(self::TABLE)
                ->wherePrimary($headerId)
                ->update([
                    self::COLUMN_PARENT_ID => $parentHeaderId,
                    self::COLUMN_POSITION  => ($position * 2) - 1,
                ]);
        });

        $this->adjustPositionsAround($this->getById($headerId));
    }

    private function adjustPositionsAround(HeaderWrapper $wrapper) {
        if ($wrapper->getParentId() === 0) $this->adjustPositionsUnderLang($wrapper->getLanguageId());
        else $this->adjustPositionsUnderId($wrapper->getParentId());
    }

    private function adjustPositionsUnderId(int $id) {
        if (!$this->exists($id)) throw new InvalidArgumentException("Header $id does not exist");

        $this->runInTransaction(function () use ($id) {
            foreach ($this->getChildrenIds($id) as $key => $childrenId) {
                $this->updatePosition($childrenId, $key);
            }
        });
    }

    private function updatePosition(int $childrenId, int $key) {
        $this->runInTransaction(function () use ($childrenId, $key) {
            $this->getDatabase()->table(self::TABLE)
                ->wherePrimary($childrenId)
                ->update([
                    self::COLUMN_POSITION => $key * 2,
                ]);
        });
    }

    private function adjustPositionsUnderLang(int $langId) {
        $this->runInTransaction(function () use ($langId) {
            foreach ($this->getRootChildrenIds($langId) as $key => $childrenId) {
                $this->updatePosition($childrenId, $key);
            }
        });
    }

    public function canChangeParent(int $headerId, int $parentHeaderId): bool {
        if ($parentHeaderId === 0) return true;
        if ($headerId === $parentHeaderId) return false;
        $parentWrapper = $this->getById($parentHeaderId);
        return $this->canChangeParent($headerId, $parentWrapper->getParentId());
    }

    private function getChildrenIds(int $parentId): array {
        $childrenRows = $this->getDatabase()->table(self::TABLE)
            ->where([self::COLUMN_PARENT_ID => $parentId])
            ->select(self::COLUMN_ID)
            ->order(self::COLUMN_POSITION)
            ->fetchAll();

        return array_map(function (IRow $row) {
            return $row[self::COLUMN_ID];
        }, $childrenRows);
    }
}