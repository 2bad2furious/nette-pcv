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

    protected function init() {
        PageManager::on(IPageManager::TRIGGER_PAGE_DELETED, function (int $globalId) {
            $data = $this->getDatabase()->table(self::TABLE)
                ->where([self::COLUMN_PAGE_ID => $globalId]);

            while ($row = $data->fetch()) {
                try {
                    $this->delete($row[self::COLUMN_ID]);
                } catch (Exception $x) {
                    \Tracy\Debugger::log($x);
                }
            }
        });
        LanguageManager::on(LanguageManager::TRIGGER_LANGUAGE_DELETED, function (Language $language) {
            $this->getCache()->remove($language->getCode());

            $this->getCache()->clean([Cache::TAGS => [
                //TODO fill
            ]]);

            $this->runInTransaction(function () use ($language) {
                $data = $this->getDatabase()->table(self::TABLE)->where([
                    self::COLUMN_LANG => $language->getId(),
                ]);
                while ($row = $data->fetch()) {
                    $this->deleteBranch($row[self::COLUMN_ID]);
                }
            });
        });
    }


    public function cleanCache() {
        $this->getCache()->clean();
    }

    /**
     * @param int $languageId
     * @return HeaderWrapper[]
     */
    public function getHeader(int $languageId): array {
        return $this->getRootChildren($languageId);
    }

    /**
     * @param int $id
     * @param bool $throw
     * @return HeaderWrapper|null
     */
    public function getById(int $id, bool $throw = true): ?HeaderWrapper {
        $cached = $this->getPlainById($id, false);

        if ($cached instanceof Header) {
            return $this->constructHeaderWrapper($cached);
        } else if ($throw) throw new HeaderNotFound($id);
        return null;
    }

    public function addPage(int $parentId, int $languageId, int $pageId, string $title): HeaderWrapper {
        if ($parentId !== 0) {
            $header = $this->getPlainById($parentId);
            if ($header->getLanguageId() !== $languageId) throw new InvalidArgumentException("Languages are not the same; $languageId != {$header->getLanguageId()}");
        }

        /* throws exceptions if they dont exist */
        $this->getLanguageManager()->getById($languageId);

        $this->getPageManager()->getByGlobalId($languageId, $pageId);
        /* end of exception throwing */

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
        if ($parentId !== 0) {
            $header = $this->getPlainById($parentId);
            if ($header->getLanguageId() !== $languageId) throw new InvalidArgumentException("Languages are not the same; $languageId != {$header->getLanguageId()}");
        }

        $this->getLanguageManager()->getById($languageId);

        if (!preg_match("#^" . $this->getUrlPattern() . "$#", $url)) throw new InvalidArgumentException("Url {$url} is not valid");

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
        $header = $this->getPlainById($headerId);

        $this->getPageManager()->getByGlobalId($header->getLanguageId(), $pageId);

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
     * @param int $languageId
     * @return HeaderWrapper[]
     */
    private function getRootChildren(int $languageId) {
        return array_map(function (int $id) {
            return $this->getById($id);
        }, $this->getRootChildrenIds($languageId));
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
        $langId = $header->getLanguageId();

        return new HeaderWrapper(
            $header,
            $this->getLanguageManager(),
            $this->getPageManager(),
            $this
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

        $header = $this->getPlainById($headerId);

        if ($parentHeaderId !== 0) {
            $parent = $this->getPlainById($parentHeaderId);

            if ($parent->getLanguageId() !== $header->getLanguageId()) throw new InvalidArgumentException("Parent is not of the same language; {$parent->getLanguageId()} !== {$header->getLanguageId()}");
        }
        if (!$this->canChangeParent($headerId, $parentHeaderId)) throw new InvalidArgumentException("New parent $parentHeaderId is already a descendant of $headerId");

        $this->uncache($headerId);
        $this->uncache($parentHeaderId);
        $this->uncache($header->getParentId());

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
        $currentToBeMoved = $this->getPlainById($headerId);
        $currentParent = $this->getPlainById($currentToBeMoved->getParentId());

        $this->runInTransaction(function () use ($currentParent, $currentToBeMoved) {
            $this->uncache($currentToBeMoved->getId());
            $this->uncache($currentParent->getId());

            $this->getDatabase()->table(self::TABLE)
                ->wherePrimary($currentToBeMoved->getId())
                ->update([
                    self::COLUMN_PARENT_ID => $currentParent->getParentId(),
                    self::COLUMN_POSITION  => $currentParent->getPosition() + 1,
                ]);

            $this->adjustPositionsAround($currentToBeMoved);
            $this->adjustPositionsAround($currentParent);
        });
    }

    public function canBeMovedLeft(int $headerId): bool {
        return $this->getPlainById($headerId)->getParentId() !== 0;
    }

    public function moveRight(int $headerId) {
        dump("moving right", $headerId);
        if (!$this->canBeMovedRight($headerId)) throw new InvalidArgumentException("Header $headerId cannot be moved right");

        $header = $this->getPlainById($headerId);
        $upperSibling = $this->getUpperSibling($headerId);

        dump($upperSibling);

        $this->uncache($headerId);
        $this->uncache($upperSibling->getId());
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
     * @param bool $throw
     * @return false|Header
     */
    private function getPlainById(int $id, bool $throw = true) {
        $cached = $this->getCache()->load($id, function (&$dependencies) use ($id) {
            //TODO set TAGS such as lang, page_id
            return $this->getFromDb($id);
        });

        if (!$cached && $throw) throw new InvalidArgumentException("Header $id not found");
        return $cached;
    }

    private function getUpperSibling(int $headerId): ?Header {
        $header = $this->getPlainById($headerId);

        $siblingId = $this->getDatabase()->table(self::TABLE)
            ->where([
                self::COLUMN_PARENT_ID       => $header->getParentId(),
                self::COLUMN_LANG            => $header->getLanguageId(),
                self::COLUMN_POSITION . " <" => $header->getPosition(),
            ])
            ->order(self::COLUMN_POSITION . " DESC")
            ->fetchField(self::COLUMN_ID);

        return is_int($siblingId) ? $this->getPlainById($siblingId) : null;
    }

    public function getUrlPattern(): string {
        return "[a-zA-Z0-9\#_/+-]+";
    }
}