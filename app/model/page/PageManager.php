<?php


use Nette\Database\IRow;

class PageManager extends Manager implements IPageManager {
    const MAIN_TABLE = "page",
        MAIN_COLUMN_ID = "page_id",
        MAIN_COLUMN_TYPE = "type", /** int 1 if page 0 if post more at @var Type */
        TYPES = [self::TYPE_PAGE, self::TYPE_POST],
        MAIN_COLUMN_STATUS = "global_status", STATUS_ALL = null, STATUS_PUBLIC = 1, STATUS_DELETED = -1, STATUS_DRAFT = 0,
        STATUSES = [self::STATUS_PUBLIC, self::STATUS_DRAFT],
        MAIN_COLUMN_PARENT_ID = "parent_id",

        LOCAL_TABLE = "page_local",
        LOCAL_COLUMN_ID = "page_local_id",
        LOCAL_MAIN_COLUMN_ID = "page_id",
        LOCAL_COLUMN_TITLE = "title", LOCAL_COLUMN_TITLE_LENGTH = 60,
        LOCAL_COLUMN_URL = "url", LOCAL_COLUMN_URL_LENGTH = 60, LOCAL_URL_CHARSET = "[a-zA-Z0-9_+-/]+", LOCAL_URL_CHARSET_FOR_ADMIN = "[a-zA-Z0-9_+-]+",
        LOCAL_COLUMN_DESCRIPTION = "description", LOCAL_COLUMN_DESCRIPTION_LENGTH = 255,
        LOCAL_COLUMN_IMAGE = "image",
        LOCAL_COLUMN_VIDEO = "video", LOCAL_COLUMN_VIDEO_LENGTH = 255,
        LOCAL_COLUMN_LANG = "lang_id",
        LOCAL_COLUMN_STATUS = "local_status",
        LOCAL_COLUMN_CONTENT = "content",
        LOCAL_COLUMN_CREATED = "created",
        LOCAL_COLUMN_LAST_EDITED = "last_edited",
        LOCAL_COLUMN_AUTHOR = "author",
        LOCAL_COLUMN_DISPLAY_TITLE = "display_title",
        LOCAL_COLUMN_DISPLAY_BREADCRUMBS = "display_breadcrumbs",

        LOCAL_SEARCH = self::LOCAL_TABLE . "." . "content," . self::LOCAL_TABLE . "." . "title," . self::LOCAL_TABLE . "." . "url," . self::LOCAL_TABLE . "." . "description",

        CHANGES_TABLE = "page_content_change",
        CHANGE_COLUMN_ID = "change_id",
        CHANGE_LOCAL_COLUMN_ID = "page_local_id",
        CHANGE_AUTHOR_ID = "author",
        CHANGE_COLUMN_DATE = "date",
        CHANGE_COLUMN_PRE_CONTENT = "pre_content",
        CHANGE_COLUMN_AFTER_CONTENT = "after_content";

    const RANDOM_URL_PREFIX = "generated-url-";

    protected function init() {
        LanguageManager::on(ILanguageManager::TRIGGER_LANGUAGE_DELETED, function (Language $language) {
            $this->getUrlCache()->clean([Cache::TAGS => $this->getLanguageTag($language->getId())]);
            $this->getGlobalCache()->clean([Cache::TAGS => $this->getLanguageTag($language->getId())]);

            $this->getDatabase()->table(self::LOCAL_TABLE)->where([
                self::LOCAL_COLUMN_LANG => $language->getId(),
            ])->delete();
        });
        LanguageManager::on(LanguageManager::TRIGGER_LANGUAGE_ADDED, function (Language $language) {
            $data = $this->getDatabase()->table(self::MAIN_TABLE)->select(self::MAIN_COLUMN_ID);
            while ($id = $data->fetchField(self::MAIN_COLUMN_ID)) {
                $this->addLocal($language, $id, $this->getFreeUrl($language));
            }
        });
    }


    public function exists(int $globalId, ?int $languageId = null, bool $throw = false): bool {
        $exists = (is_int($languageId)) ?
            $this->getPlainById($globalId, $languageId, $throw) instanceof APage :
            $this->getDatabase()->table(self::MAIN_TABLE)
                ->where([self::MAIN_TABLE . "." . self::MAIN_COLUMN_ID => $globalId])
                ->fetch() instanceof IRow;

        if (!$exists && $throw) throw new PageNotFound($globalId, (int)$languageId);

        return $exists;
    }

    public function getAllPages(int $languageId, ?int $type = self::TYPE_ALL): array {
        $data = $this->getDatabase()->table(self::LOCAL_TABLE)
            ->where([self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_LANG => $languageId]);

        $pages = [];
        while ($row = $data->fetch()) {
            $globalId = $row[self::LOCAL_MAIN_COLUMN_ID];
            $page = $this->getByGlobalId($languageId, $globalId);
            $pages[$globalId] = $page;
        }
        return $pages;
    }

    /**
     * @param int $globalId
     * @param int $languageId
     * @return array
     */
    public function getViableParents(int $globalId, int $languageId): array {
        $page = $this->getPlainById($globalId, $languageId);
        if ($page->isPage()) {
            $allowedTypes = [self::TYPE_PAGE];
        } else throw new InvalidArgumentException("Invalid Type");

        $invalidGlobalIds = $this->getInvalidParents($globalId);

        $pages = $this->getDatabase()->table(self::LOCAL_TABLE)->where(
            [self::LOCAL_TABLE . "." . self::LOCAL_MAIN_COLUMN_ID . " NOT ?" => $invalidGlobalIds,
             self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_LANG               => $languageId,
             self::MAIN_TABLE . "." . self::MAIN_COLUMN_TYPE                 => $allowedTypes]
        );

        $parents = [];
        while ($parent = $pages->fetch()) {
            $parents[$parent[self::LOCAL_MAIN_COLUMN_ID]] = $this->getByGlobalId($languageId, $parent[self::LOCAL_MAIN_COLUMN_ID]);
        }
        return $parents;
    }

    /**
     * @param int $globalId
     * @return int[] globalIds
     */
    private function getInvalidParents(int $globalId): array {
        $invalidParents = [$globalId];

        $children = $this->getDatabase()->table(self::MAIN_TABLE)->where([self::MAIN_TABLE . "." . self::MAIN_COLUMN_PARENT_ID => $globalId])->select(self::MAIN_TABLE . "." . self::MAIN_COLUMN_ID);

        while ($child = $children->fetch()) {
            $invalidParents = array_merge($invalidParents, $this->getInvalidParents($child[self::MAIN_COLUMN_ID]));
        }
        return $invalidParents;
    }

    /**
     * @param int|null $type
     * @param int|null $visibility
     * @param Language|null $language
     * @param bool|null $hasTranslation
     * @param int $page
     * @param int $perPage
     * @param &$numOfPages
     * @param null|string $search
     * @return PageWrapper[]
     * @throws InvalidArgumentException for bad type|visibilty
     */
    public function getFiltered(?int $type = null, ?int $visibility = null, ?Language $language, ?bool $hasTranslation, int $page, int $perPage, &$numOfPages, ?string $search) {
        $selection = $this->getDatabase()->table(self::LOCAL_TABLE)
            ->group(self::LOCAL_TABLE . "." . self::LOCAL_MAIN_COLUMN_ID)
            ->select("GROUP_CONCAT(" . self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_ID . " SEPARATOR '|') localIds")
            ->select(self::LOCAL_TABLE . "." . self::LOCAL_MAIN_COLUMN_ID)
            ->order(self::LOCAL_COLUMN_LANG)
            ->order(self::LOCAL_TABLE . "." . self::LOCAL_MAIN_COLUMN_ID);

        if (is_int($type)) $selection = $selection->where(self::MAIN_TABLE . "." . self::MAIN_COLUMN_TYPE, $type);

        if (is_string($search)) {
            $searchWheres = [];
            $values = [];
            $explosion = explode(" ", $search);
            foreach ($explosion as $item) {
                if (($possibleId = intval($item)) !== 0) {
                    $values[] = $possibleId;
                }
                $searchWheres[] = "MATCH(" . self::LOCAL_SEARCH . ") AGAINST (? IN BOOLEAN MODE)";
            }

            $whereData = ["MATCH(" . self::LOCAL_SEARCH . ") AGAINST (? IN BOOLEAN MODE)" =>
                              implode(" ", array_map(function (string $item) {
                                  return "%" . $item . "%";
                              }, $explosion)),];
            if ($values) {
                $whereData[self::MAIN_TABLE . "." . self::MAIN_COLUMN_ID] = $values;
                $whereData[self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_ID] = $values;
            }

            $selection = $selection->whereOr($whereData);

            //$selection = $selection->where(["(" . implode(") OR (", $searchWheres) . ")"=> $values]);
        }

        if ($language instanceof Language && is_bool($hasTranslation)) {
            $selection = $selection->where(
                [self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_CONTENT . ($hasTranslation ? " != ?" : "") => "",
                 self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_LANG                                       => $language->getId(),]
            );
        }

        $leastVisibility = "LEAST(" . self::MAIN_TABLE . "." . self::MAIN_COLUMN_STATUS . "," . self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_STATUS . ")";
        /* only some visibility vs all */
        if (is_int($visibility)) {
            $selection = $selection->where([$leastVisibility => $visibility]);
        }

        $result = $selection->page($page, $perPage, $numOfPages);
        if ($numOfPages === 0) $numOfPages = 1;
        $pages = [];

        while ($page = $result->fetch()) {
            $localPages = $this->getByLocalIds(explode("|", $page["localIds"]));
            $pages[$page[self::LOCAL_MAIN_COLUMN_ID]] = $localPages;
        }
        return $pages;
    }

    public function cleanCache() {
        $this->getGlobalCache()->clean();
        $this->getUrlCache()->clean();
    }

    /**
     * @param int $languageId
     * @param int $id PageId
     * @param bool $throw
     * @return null|PageWrapper
     * @throws PageNotFound
     */
    public function getByGlobalId(int $languageId, int $id, bool $throw = true): ?PageWrapper {
        $cached = $this->getPlainById($id, $languageId, $throw);
        $result = $cached instanceof APage ? $this->getIfRightsAndSetMissing($cached) : null;
        return $result;
    }

    /**
     * @param int $languageId
     * @param string $url
     * @return null|PageWrapper
     */
    public function getByUrl(int $languageId, string $url): ?PageWrapper {
        $cached = $this->getUrlCache()->load($this->getUrlCacheKey($url, $languageId), function (&$dependencies) use ($languageId, $url) {
            $page = $this->getPlainFromDbByUrl($url, $languageId);
            $dependencies = self::getDependencies($page instanceof Page ? $page->getGlobalId() : -1, $languageId);
            return $page;
        });

        return $cached instanceof Page ? $this->getIfRightsAndSetMissing($cached) : null;
    }

    private function getIfRightsAndSetMissing(APage $page): ?PageWrapper {
        if ($page->getStatus() >= self::STATUS_PUBLIC || $this->getUser()->isAllowed(self::ACTION_SEE_NON_PUBLIC_PAGES)) {
            return new PageWrapper($page,
                $this,
                $this->getLanguageManager(),
                $this->getSettingsManager(),
                $this->getUserManager(),
                $this->getMediaManager()
            );
        }
        return null;
    }

    /**
     * @param int $languageId
     * @return null|PageWrapper
     * @throws LanguageByIdNotFound
     */
    public function get404(int $languageId): ?PageWrapper {
        $language = $this->getLanguageManager()->getById($languageId);
        return $this->getByGlobalId($languageId, $language->getErrorpageId(), false);
    }

    /**
     * @param string $url
     * @param int $languageId
     * @param int|null $localId
     * @return bool
     */
    public function isUrlAvailable(string $url, int $languageId, ?int $localId): bool {
        $data = $this->getDatabase()->table(self::LOCAL_TABLE)
            ->where([
                self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_LANG => $languageId,
                self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_URL  => $url,
            ]);
        if (is_int($localId))
            $data = $data->where([self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_ID . " != " => $localId]);

        return !$data->fetch() instanceof IRow;
    }

    /**
     * @param int $type (page|post)
     * @return int ID of the page added
     * @throws InvalidArgumentException|Exception
     * @throws Throwable
     */
    public function addEmpty(int $type): int {
        if (!in_array($type, self::TYPES)) throw new InvalidArgumentException("Invalid type " . implode("|", self::TYPES) . " needed, $type got.");

        $languages = [];
        $globalId = 0;

        $this->runInTransaction(function () use ($type, $languages, $globalId) {
            $globalId = $this->getDatabase()->table(self::MAIN_TABLE)->insert([
                self::MAIN_TABLE . "." . self::MAIN_COLUMN_TYPE      => $type,
                self::MAIN_TABLE . "." . self::MAIN_COLUMN_STATUS    => self::STATUS_DRAFT,
                self::MAIN_TABLE . "." . self::MAIN_COLUMN_PARENT_ID => 0,
            ])->getPrimary();

            $languages = $this->getLanguageManager()->getAvailableLanguages();

            foreach ($languages as $language) {
                $url = $this->getFreeUrl($language);

                $this->addLocal($language, $globalId, $url);

                $this->urlUncache($url, $language->getId());
            }

            return $languages;
        });

        $localPages = [];

        foreach ($languages as $language) {
            $localPages[] = $this->getByGlobalId($language, $globalId);
        }

        $this->trigger(self::TRIGGER_PAGE_ADDED, $localPages);
        return $globalId;
    }

    /**
     * @param int $pageId
     * @param int $langId
     * @param int|null $parentId
     * @param string $title
     * @param string $description
     * @param string $url
     * @param int $globalVisibility
     * @param int $localVisibility
     * @param string $content
     * @param int $imageId
     * @throws Exception
     * @throws InvalidState
     * @throws PageNotFound
     * @throws Throwable
     */
    public function update(int $pageId, int $langId, ?int $parentId, string $title, string $description, string $url, int $globalVisibility, int $localVisibility, string $content, int $imageId) {

        $page = $this->getPlainById($pageId, $langId);

        $identity = $this->getUser()->getIdentity();
        if (!$identity instanceof UserIdentity) throw new InvalidState("User not logged in");

        if (!in_array($localVisibility, self::STATUSES))
            throw new InvalidArgumentException("Local visiblity $localVisibility is not in " . implode("|", self::STATUSES));

        if (!in_array($globalVisibility, self::STATUSES))
            throw new InvalidArgumentException("Global visiblity $globalVisibility is not in " . implode("|", self::STATUSES));

        $parentOk = $parentId === 0 || is_null($parentId);
        if (!$parentOk)
            foreach ($this->getViableParents($page->getGlobalId(), $page->getLanguageId()) as $possibleParent) {
                if ($possibleParent->getGlobalId() === $parentId) {
                    $parentOk = true;
                    break;
                }
            }

        if (!$parentOk) {
            if (!$this->getByGlobalId($page->getLanguageId(), $parentId) instanceof PageWrapper)
                throw new InvalidArgumentException("Parent does not exist");

            if (in_array($parentId, $this->getInvalidParents($page->getGlobalId())))
                throw new InvalidArgumentException("Parent is invalid - descendant");
        }

        if (!preg_match("#" . self::LOCAL_URL_CHARSET_FOR_ADMIN . "#", $url))
            throw new Exception("URL not the right pattern: " . self::LOCAL_URL_CHARSET);

        $image = $imageId === 0 ? null : $this->getMediaManager()->getById($imageId, FileManager::TYPE_IMAGE);
        if ($imageId !== 0 && !$image instanceof File)
            throw new InvalidArgumentException("Image not found");

        //uncache
        $this->urlUncache($page->getUrl(), $page->getLanguageId());
        $this->globalUncache($page->getGlobalId(), $page->getLanguageId());

        $newPage = $this->runInTransaction(function () use ($parentId, $page, $globalVisibility, $url, $title, $description, $content, $image, $localVisibility) {
            if ($parentId !== $page->getParentId() || $globalVisibility !== $page->getGlobalStatus()) {

                //uncache parent
                if ($page->getParentId()) {
                    $parent = $this->getPlainById($page->getParentId(), $page->getLanguageId(), true);
                    $this->globalUncache($parent->getParentId(), $parent->getLanguageId());
                    if ($parent instanceof APage) $this->urlUncache($parent->getUrl(), $page->getParentId());
                }
                //uncache future? parent
                if ($parentId !== 0) {
                    $futureParent = $this->getPlainById($parentId, $page->getLanguageId(), true);
                    $this->globalUncache($parentId, $page->getLanguageId());//
                    $this->urlUncache($futureParent, $page->getLanguageId());
                }

                $updateData = [self::MAIN_TABLE . "." . self::MAIN_COLUMN_STATUS => $globalVisibility,];
                if (is_int($parentId)) $updateData[self::MAIN_TABLE . "." . self::MAIN_COLUMN_PARENT_ID] = $parentId;
                $this->getDatabase()
                    ->table(self::MAIN_TABLE)
                    ->where([
                        self::MAIN_TABLE . "." . self::MAIN_COLUMN_ID => $page->getGlobalId(),
                    ])
                    ->update($updateData);
            }

            $this->getDatabase()
                ->table(self::LOCAL_TABLE)
                ->where([
                    self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_ID => $page->getLocalId(),
                ])
                ->update([
                    self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_URL         => $url,
                    self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_TITLE       => $title,
                    self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_DESCRIPTION => $description,
                    self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_CONTENT     => $content,
                    self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_IMAGE       => $image,
                    self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_STATUS      => $localVisibility,
                    self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_LAST_EDITED => new \Nette\Utils\DateTime(),
                ]);


            /** @var APage $newPage */
            $newPage = $this->getPlainById($page->getGlobalId(), $page->getLanguageId());

            if ($content !== $page->getContent()) {
                $this->getDatabase()->table(self::CHANGES_TABLE)->insert([
                    self::CHANGE_LOCAL_COLUMN_ID      => $page->getLocalId(),
                    self::CHANGE_AUTHOR_ID            => $this->getUser()->getId(),
                    self::CHANGE_COLUMN_PRE_CONTENT   => $page->getContent(),
                    self::CHANGE_COLUMN_AFTER_CONTENT => $newPage->getContent(),
                ]);
            }

            return $newPage;
        }, function () use ($page) {
            $this->urlUncache($page->getUrl(), $page->getLanguageId());
            $this->globalUncache($page->getGlobalId(), $page->getLanguageId());
        });


        //force header and other shit to update
        $this->trigger(self::TRIGGER_PAGE_EDITED, $newPage);
    }

    /**
     * @param int $globalId
     * @throws InvalidArgumentException|Exception
     * @throws Throwable
     */
    public function delete(int $globalId) {
        if (!$this->exists($globalId)) throw new InvalidArgumentException("Page not found");

        foreach ($this->getLanguageManager()->getAvailableLanguages() as $language) {
            if ($language->getHomepageId() == $globalId)
                throw new InvalidArgumentException("Is at least one homepage");

        }

        $parentId = $this->getParentOf($globalId);

        $this->getGlobalCache()->clean($tags = [Cache::TAGS => [
            self::getGlobalTag($globalId),
            self::getGlobalTag($parentId),
        ]]);
        $this->getUrlCache()->clean($tags);


        $this->runInTransaction(function () use ($globalId) {
            $this->getDatabase()->table(self::LOCAL_TABLE)
                ->where([self::LOCAL_TABLE . "." . self::LOCAL_MAIN_COLUMN_ID => $globalId])->delete();
            $this->getDatabase()->table(self::MAIN_TABLE)
                ->where([self::MAIN_COLUMN_ID => $globalId])->delete();
        });
    }

    private function getUrlCache(): Cache {
        static $urlCache = null;
        return $urlCache instanceof Cache ? $urlCache : $urlCache = $this->getCache()->derive("url");
    }

    private function getGlobalCache(): Cache {
        static $globalCache = null;
        return $globalCache instanceof Cache ? $globalCache : $globalCache = $this->getCache()->derive("global");
    }

    private function getCache(): Cache {
        static $cache = null;
        return $cache instanceof Cache ? $cache : $cache = new Cache($this->getDefaultStorage(), "page");
    }

    private function getGlobalCacheKey(int $id, int $languageId) {
        return $languageId . "\$" . $id;
    }

    private function getUrlCacheKey(string $url, int $languageId) {
        return $languageId . "\$" . $url;
    }

    private static function getDependencies(int $globalId, int $languageId): array {
        return array_merge(self::getTags($globalId, $languageId));
    }

    private static function getTags(int $globalId, int $languageId) {
        return [Cache::TAGS => [
            self::getGlobalTag($globalId),
            self::getLanguageTag($languageId),
        ]];
    }

    private static function getGlobalTag(int $globalId) {
        return "global_" . $globalId;
    }

    private static function getLanguageTag(int $languageId) {
        return "language_" . $languageId;
    }

    private function getPlainById(int $pageId, int $languageId, bool $throw = true): ?APage {
        $cached = $this->getGlobalCache()->load($this->getGlobalCacheKey($pageId, $languageId), function (&$dependencies) use ($languageId, $pageId) {
            $dependencies = self::getDependencies($pageId, $languageId);
            return $this->getPlainFromDbByGlobalId($pageId, $languageId);
        });
        if (!$cached instanceof APage && $throw) throw new PageNotFound($pageId, $languageId);
        return $cached instanceof APage ? $cached : null;
    }

    /**
     * @param int $id
     * @param int $languageId
     * @return false|Page
     */
    private function getPlainFromDbByGlobalId(int $id, int $languageId) {
        return $this->getPlainFromDb([
            self::LOCAL_TABLE . "." . self::LOCAL_MAIN_COLUMN_ID => $id,
            self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_LANG    => $languageId,
        ]);
    }

    /**
     * @param string $url
     * @param int $languageId
     * @return false|APage
     */
    private function getPlainFromDbByUrl(string $url, int $languageId) {
        return $this->getPlainFromDb([
            self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_LANG => $languageId,
            self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_URL  => $url,
        ]);
    }

    /**
     * @param array $where
     * @return false|APage
     */
    private function getPlainFromDb(array $where) {
        $data = $this->getDatabase()->table(self::LOCAL_TABLE)
            ->where($where)
            ->select(self::MAIN_TABLE . ".*")
            ->select(self::LOCAL_TABLE . ".*")
            ->fetch();

        return $data instanceof IRow ? $this->createFromRow($data) : false;
    }

    private function createFromRow(IRow $row): APage {
        $className = APage::CLASS_BY_TYPE[$row[self::MAIN_COLUMN_TYPE]];

        return new $className(
            $globalId = $row[self::MAIN_COLUMN_ID],
            $row[self::LOCAL_COLUMN_ID],
            $row[self::MAIN_COLUMN_PARENT_ID],
            $row[self::LOCAL_COLUMN_LANG],
            $row[self::LOCAL_COLUMN_TITLE],
            $row[self::LOCAL_COLUMN_URL],
            $row[self::LOCAL_COLUMN_DESCRIPTION],
            $row[self::LOCAL_COLUMN_CONTENT],
            $row[self::LOCAL_COLUMN_AUTHOR],
            $row[self::LOCAL_COLUMN_CREATED],
            $row[self::LOCAL_COLUMN_LAST_EDITED],
            $row[self::LOCAL_COLUMN_IMAGE],
            $row[self::MAIN_COLUMN_STATUS],
            $row[self::LOCAL_COLUMN_STATUS],
            false, //TODO get from db
            false,
            $this->getChildrenIds($globalId)
        );
    }

    private function getFreeUrl(Language $language): string {
        $unique = self::RANDOM_URL_PREFIX . sha1(uniqid());

        if (!$this->getDatabase()->table(self::LOCAL_TABLE)->where([
            self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_URL  => $unique,
            self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_LANG => $language->getId(),
        ])->fetch()) return $unique; /* if !exists */

        return $this->getFreeUrl($language);
    }

    private function urlUncache(string $url, int $languageId) {
        $this->getUrlCache()->remove($this->getUrlCacheKey($url, $languageId));
    }

    private function globalUncache(int $globalId, int $langId) {
        $this->getGlobalCache()->remove($this->getGlobalCacheKey($globalId, $langId));
        $this->getGlobalCache()->clean($tags = self::getTags($globalId, $langId));
        $this->getUrlCache()->clean($tags);
    }

    /**
     * @param int[] $localIds
     * @return PageWrapper[]
     */
    private function getByLocalIds(array $localIds): array {
        $pages = [];
        foreach ($localIds as $localId) {
            $data = $this->getDatabase()->table(self::LOCAL_TABLE)
                ->where([self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_ID => $localId])
                ->select(self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_LANG)
                ->select(self::LOCAL_TABLE . "." . self::LOCAL_MAIN_COLUMN_ID)
                ->fetch();

            if (!$data instanceof IRow) throw new InvalidArgumentException();

            $langId = $data[self::LOCAL_COLUMN_LANG];
            $globalId = $data[self::LOCAL_MAIN_COLUMN_ID];
            $pages[$localId] = $this->getByGlobalId($langId, $globalId);
        }
        return $pages;
    }

    public static function isDefaultUrl(string $url): bool {
        return substr($url, 0, strlen(self::RANDOM_URL_PREFIX)) === self::RANDOM_URL_PREFIX;
    }

    /**
     * @param int $globalId
     * @return int[]
     */
    private function getChildrenIds(int $globalId): array {
        $data = $this->getDatabase()->table(self::MAIN_TABLE)
            ->where([self::MAIN_COLUMN_PARENT_ID => $globalId])
            ->select(self::MAIN_COLUMN_ID);

        $childrenIds = [];
        while ($row = $data->fetch()) {
            $childrenIds[] = $row[self::MAIN_COLUMN_ID];
        }
        return $childrenIds;
    }

    private function getParentOf(int $globalId): int {
        return $this->getDatabase()->table(self::MAIN_TABLE)->wherePrimary($globalId)->fetchField(self::MAIN_COLUMN_PARENT_ID);
    }

    private function addLocal(Language $language, int $globalId, string $url) {
        return $this->getDatabase()->table(self::LOCAL_TABLE)->insert([
            self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_STATUS  => self::STATUS_DRAFT,
            self::LOCAL_TABLE . "." . self::LOCAL_MAIN_COLUMN_ID => $globalId,
            self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_LANG    => $language->getId(),
            self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_TITLE   => self::DEFAULT_TITLE,
            self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_URL     => $url,
            self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_AUTHOR  => $this->getUser()->getIdentity()->getId(),
        ])->getPrimary();
    }
}