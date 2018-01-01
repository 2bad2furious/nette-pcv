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

    protected function init() {
        LanguageManager::on(LanguageManager::TRIGGER_LANGUAGE_DELETED, function (Language $language) {
            $this->getCache()->remove($language->getCode());

            $this->getCache()->clean([Cache::TAGS => [
                //TODO fill
            ]]);

            $this->runInTransaction(function () use ($language) {
                $this->getDatabase()->table(self::TABLE)->where([
                    self::COLUMN_LANG => $language->getId(),
                ])->delete();
            });
        });
    }


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
        $cached = $this->getPlainById($id);

        if ($cached instanceof Header) {
            return $this->constructHeaderWrapper($cached);
        }
        return null;
    }

    private function getChildren(Header $header, bool &$active): array {
        \Tracy\Debugger::log("children of {$header->getId()}");
        \Tracy\Debugger::log($header->getChildrenIds());
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

            $header = $this->getPlainById($headerId);

            $this->adjustPositionsAround($header);

            return $this->constructHeaderWrapper($header);
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


            $header = $this->getPlainById($headerId);;

            $this->adjustPositionsAround($header);

            return $this->constructHeaderWrapper($header);
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
        $header = $this->getPlainById($id);
        if (!$header instanceof Header) throw new InvalidArgumentException("Header {$id} not found");

        $this->uncache($id);

        $this->runInTransaction(function () use ($header) {
            foreach ($this->getChildrenIds($header->getId()) as $childrenId) {
                $this->moveLeft($childrenId);
            }

            $affected = $this->getDatabase()->table(self::TABLE)->wherePrimary($header->getId())->delete();

            $this->adjustPositionsAround($header);

            return $affected;
        });

    }

    public function deleteBranch(int $id) {
        $header = $this->getPlainById($id);
        if (!$header instanceof Header) throw new InvalidArgumentException("Header {$id} not found");

        $this->uncache($id);

        $this->runInTransaction(function () use ($header) {
            $children = $this->getDatabase()->table(self::TABLE)->where([self::COLUMN_PARENT_ID => $header->getId()])->fetchAll();

            foreach ($children as $child) {
                $this->deleteBranch($child[self::COLUMN_ID]);
            }

            $this->delete($header->getId());


            $this->adjustPositionsAround($header);
        });
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

        $childrenIds = $this->getChildrenIds($data[self::COLUMN_ID]);

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
        \Tracy\Debugger::log("root - {$language->getId()}");
        \Tracy\Debugger::log($this->getRootChildrenIds($language->getId()));
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
        }, array_values($data)); //dodge keeping keys - primary
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
        $header = $this->getPlainById($id);
        if (!$header instanceof Header) return false;

        if (is_int($langId) && $header->getLanguageId() !== $langId) return false;

        return true;
    }

    private function uncache($key) {
        $this->getCache()->remove($key);
    }

    public function changeParentOrPosition(int $headerId, int $parentHeaderId, int $position) {
        if ($headerId === $parentHeaderId) throw new InvalidArgumentException("$headerId: Ids are the same");

        $headerWrapper = $this->getPlainById($headerId);
        if (!$headerWrapper instanceof Header) throw new InvalidArgumentException("Header $headerId not found");

        if ($parentHeaderId !== 0 && !$this->exists($parentHeaderId)) throw new InvalidArgumentException("Parent $parentHeaderId not found");

        if ($parentHeaderId !== 0 && !$this->exists($parentHeaderId, $headerWrapper->getLanguageId())) throw new InvalidArgumentException("Parent is not of the same language");

        if (!$this->canChangeParent($headerId, $parentHeaderId)) throw new InvalidArgumentException("New parent $parentHeaderId is already a descendant of $headerId");

        $this->uncache($headerId);
        $this->uncache($parentHeaderId);
        $this->uncache($headerWrapper->getParentId());

        $this->runInTransaction(function () use ($headerId, $parentHeaderId, $position) {
            $affected = $this->getDatabase()->table(self::TABLE)
                ->wherePrimary($headerId)
                ->update([
                    self::COLUMN_PARENT_ID => $parentHeaderId,
                    self::COLUMN_POSITION  => ($position * 2) - 1,
                ]);


            $this->adjustPositionsAround($this->getPlainById($headerId));

            return $affected;
        });
    }

    private function adjustPositionsAround(Header $wrapper) {
        if ($wrapper->getParentId() === 0) $this->adjustPositionsUnderLang($wrapper->getLanguageId());
        else $this->adjustPositionsUnderId($wrapper->getParentId());
    }

    private function adjustPositionsUnderId(int $id) {
        if (!$this->exists($id)) throw new InvalidArgumentException("Header $id does not exist");

        $this->uncache($id);

        $this->runInTransaction(function () use ($id) {
            foreach ($this->getChildrenIds($id) as $key => $childrenId) {
                $this->updatePosition($childrenId, $key);
            }
        });
    }

    private function updatePosition(int $childrenId, int $position) {
        $this->runInTransaction(function () use ($childrenId, $position) {
            $this->uncache($childrenId);

            $this->getDatabase()->table(self::TABLE)
                ->wherePrimary($childrenId)
                ->update([
                    self::COLUMN_POSITION => $position * 2,
                ]);
        });
    }

    private function adjustPositionsUnderLang(int $langId) {
        $language = $this->getLanguageManager()->getById($langId);
        if (!$language instanceof Language) throw new InvalidArgumentException("Language $langId does not exist.");

        $this->uncache($language->getCode());

        $this->runInTransaction(function () use ($langId) {
            foreach ($this->getRootChildrenIds($langId) as $key => $childrenId) {
                $this->updatePosition($childrenId, $key);
            }
        });
    }

    public function canChangeParent(int $headerId, int $parentHeaderId): bool {
        if ($parentHeaderId === 0) return true;
        if ($headerId === $parentHeaderId) return false;
        $parentWrapper = $this->getPlainById($parentHeaderId);
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
        }, array_values($childrenRows));
    }

    public function moveUp(int $headerId) {
        if (!$this->canBeMovedUp($headerId)) throw new InvalidArgumentException("Header $headerId cannot be moved further up.");

        $header = $this->getPlainById($headerId);

        $this->runInTransaction(function () use ($header) {
            $this->getDatabase()->table(self::TABLE)
                ->wherePrimary($header->getId())
                ->update([self::COLUMN_POSITION => $header->getPosition() - 3]);

            $this->adjustPositionsAround($header);
        });
    }

    public function canBeMovedUp(int $headerId): bool {
        if (!$this->exists($headerId)) throw new InvalidArgumentException("Header $headerId does not exist");
        return $this->getPlainById($headerId)->getPosition() !== 0;
    }

    public function moveDown(int $headerId) {
        if (!$this->canBeMovedDown($headerId)) throw new InvalidArgumentException("Header $headerId cannot be moved further down.");

        $header = $this->getPlainById($headerId);

        $this->runInTransaction(function () use ($header) {
            $this->getDatabase()->table(self::TABLE)
                ->wherePrimary($header->getId())
                ->update([self::COLUMN_POSITION => $header->getPosition() + 3]);

            $this->adjustPositionsAround($header);
        });
    }

    public function canBeMovedDown(int $headerId): bool {
        if (!$this->exists($headerId)) throw new InvalidArgumentException("Header $headerId not found");
        $header = $this->getPlainById($headerId);

        if ($header->getParentId() !== 0) $where = [self::COLUMN_PARENT_ID => $header->getParentId()];
        else $where = [self::COLUMN_PARENT_ID => 0, self::COLUMN_LANG => $header->getLanguageId()];

        $where = $where + [self::COLUMN_POSITION . " > " => $header->getPosition()];

        $siblingsData = $this->getDatabase()->table(self::TABLE)
            ->where($where)
            ->order(self::COLUMN_POSITION)
            ->limit(1)
            ->fetch();

        return boolval($siblingsData);
    }

    public function moveLeft(int $headerId) {
        if (!$this->canBeMovedLeft($headerId)) throw new InvalidArgumentException("Header $headerId cannot be moved left");
        $header = $this->getPlainById($headerId);
        $parent = $this->getPlainById($header->getParentId());

        $this->runInTransaction(function () use ($parent, $header) {
            $this->uncache($header->getId());
            $this->uncache($parent->getId());

            $this->getDatabase()->table(self::TABLE)
                ->wherePrimary($header->getId())
                ->update([
                    self::COLUMN_PARENT_ID => $parent->getParentId(),
                    self::COLUMN_POSITION  => $parent->getPosition() + 1,
                ]);

            $this->adjustPositionsAround($header);
            $this->adjustPositionsAround($parent);
        });
    }

    public function canBeMovedLeft(int $headerId): bool {
        if (!$this->exists($headerId)) throw new InvalidArgumentException("Header $headerId does not exist");
        $header = $this->getPlainById($headerId);
        return $header->getParentId() !== 0;
    }

    public function moveRight(int $headerId) {
        if (!$this->canBeMovedRight($headerId)) throw new InvalidArgumentException("Header $headerId does not exist");

        $header = $this->getPlainById($headerId);
        $upperSibling = $this->getUpperSibling($headerId);

        $this->uncache($headerId);
        $this->uncache($header->getParentId());

        $this->runInTransaction(function () use ($header, $upperSibling) {
            $this->getDatabase()->table(self::TABLE)
                ->wherePrimary($header->getId())
                ->update([
                    self::COLUMN_PARENT_ID => $upperSibling->getId(),
                    self::COLUMN_POSITION  => count($upperSibling->getChildrenIds()) * 2 + 1,
                ]);

            $this->adjustPositionsAround($this->getPlainById($header->getId())); //sorts the new parent (former upper sibling)
            $this->adjustPositionsAround($header);//sorts the former parent
        });
    }

    public function canBeMovedRight(int $headerId): bool {
        if (!$this->exists($headerId)) throw new InvalidArgumentException("Header $headerId does not exist");
        return $this->getUpperSibling($headerId) instanceof Header;

    }

    /**
     * @param int $id
     * @return Header|false
     */
    private function getPlainById(int $id) {
        return $this->getCache()->load($id, function (&$dependencies) use ($id) {
            //TODO set TAGS such as lang, page_id
            return $this->getFromDb($id);
        });
    }

    private function getUpperSibling(int $headerId):?Header {
        $header = $this->getPlainById($headerId);
        if (!$header instanceof Header) throw new InvalidArgumentException("Header $headerId does not exist.");

        $siblingId = $this->getDatabase()->table(self::TABLE)
            ->where([
                self::COLUMN_PARENT_ID       => $header->getParentId(),
                self::COLUMN_POSITION . " <" => $header->getPosition(),
            ])
            ->order(self::COLUMN_POSITION . " DESC")
            ->fetchField(self::COLUMN_ID);

        return is_int($siblingId) ? $this->getPlainById($siblingId) : null;
    }
}