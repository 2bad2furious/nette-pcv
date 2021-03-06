<?php


use Nette\Database\IRow;

class PageManager extends Manager implements IPageManager {
    const MAIN_TABLE = "page",
        MAIN_COLUMN_ID = "page_id",
        MAIN_COLUMN_TYPE = "type", /** int 1 if page 0 if post more at @var APage */
        TYPES = [self::TYPE_PAGE, self::TYPE_POST],
        MAIN_COLUMN_STATUS = "global_status",
        MAIN_COLUMN_PARENT_ID = "parent_id",

        ORDER_TABLE = [
        self::ORDER_BY_ID           => self::LOCAL_TABLE . "." . self::LOCAL_MAIN_COLUMN_ID,
        self::ORDER_BY_TITLE        => self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_TITLE,
        self::ORDER_BY_PUBLISH_TIME => self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_CREATED,
        self::ORDER_BY_EDITED_TIME  => self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_LAST_EDITED,
    ],

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


    /**
     * @param int $globalId
     * @param int|null $languageId
     * @param bool $throw
     * @return bool
     * @throws PageNotFound
     */
    public function exists(int $globalId, ?int $languageId = null, bool $throw = false): bool {
        $exists = (is_int($languageId)) ?
            $this->getPlainById($globalId, $languageId, $throw) instanceof APage :
            $this->getDatabase()->table(self::MAIN_TABLE)
                ->where([self::MAIN_TABLE . "." . self::MAIN_COLUMN_ID => $globalId])
                ->fetch() instanceof IRow;

        if (!$exists && $throw) throw new PageNotFound($globalId, (int)$languageId);

        return $exists;
    }

    public function getAllPages(?int $languageId = null, ?int $type = self::TYPE_ALL, int $orderBy = self::ORDER_BY_ID): array {
        $data = $this->getDatabase()->table(self::LOCAL_TABLE);

        if ($languageId > 0)
            $data->where([self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_LANG => $languageId]);

        if ($type > 0)
            $data->where([self::MAIN_TABLE . "." . self::MAIN_COLUMN_TYPE => $type]);

        $data->order($this->getOrder($orderBy));

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
     * @param int $orderBy
     * @return PageWrapper[]
     */
    public function getFiltered(?int $type = null, ?int $visibility = null, ?Language $language, ?bool $hasTranslation, ?int $page, ?int $perPage, &$numOfPages, ?string $search, int $orderBy = self::ORDER_BY_ID) {
        $selection = $this->getDatabase()->table(self::LOCAL_TABLE)
            ->group(self::LOCAL_TABLE . "." . self::LOCAL_MAIN_COLUMN_ID)
            ->select("GROUP_CONCAT(" . self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_LANG . " SEPARATOR '|') langIds")
            ->select(self::LOCAL_TABLE . "." . self::LOCAL_MAIN_COLUMN_ID)
            ->order(self::LOCAL_COLUMN_LANG);

        if (is_int($type)) $selection = $selection->where(self::MAIN_TABLE . "." . self::MAIN_COLUMN_TYPE, $type);


        $order_by_column = $this->getOrder($orderBy);

        $selection->order($order_by_column);

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
                //$whereData[self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_ID] = $values; filtering by localId isn't really UX friendly since they don't know it
            }

            $selection = $selection->whereOr($whereData);

            //$selection = $selection->where(["(" . implode(") OR (", $searchWheres) . ")"=> $values]);
        }

        if (is_bool($hasTranslation))
            $selection->where([
                self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_CONTENT . ($hasTranslation ? " != ?" : "") => "",
            ]);

        if ($language instanceof Language)
            $selection->where([
                self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_LANG => $language->getId(),
            ]);


        $leastVisibility = "LEAST(" . self::MAIN_TABLE . "." . self::MAIN_COLUMN_STATUS . "," . self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_STATUS . ")";
        /* only some visibility vs all */
        if (is_int($visibility)) {
            $selection = $selection->where([$leastVisibility => $visibility]);
        }

        if ($perPage > 0 && $page > 0) {
            $result = $selection->page($page, $perPage, $numOfPages);
        } else if ($perPage > 0) {
            $result = $selection->limit($perPage);
        } else $result = $selection;


        if ($numOfPages === 0) $numOfPages = 1;
        $pages = [];

        while ($page = $result->fetch()) {
            $langIds = explode("|", $page["langIds"]);
            $globalId = $page[self::LOCAL_MAIN_COLUMN_ID];

            $pages[$globalId] = array_map(function (int $langId) use ($globalId) {
                return $this->getByGlobalId($langId, $globalId);
            }, $langIds);
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
        $result = $cached instanceof APage ? $this->createWrapper($cached) : null;
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
            $dependencies = self::getDependencies($page instanceof APage ? $page->getGlobalId() : -1, $languageId);
            return $page;
        });
        return $cached instanceof APage ? $this->createWrapper($cached) : null;
    }

    private function createWrapper(APage $page): PageWrapper {
        return new PageWrapper(
            $page,
            $this,
            $this->getLanguageManager(),
            $this->getSettingsManager(),
            $this->getAccountManager(),
            $this->getMediaManager()
        );
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


        return $this->runInTransaction(function () use ($type, $languages) {
            $id = $this->runInTransaction(function () use ($type, $languages) {
                return $this->getDatabase()->table(self::MAIN_TABLE)->insert([
                    self::MAIN_TABLE . "." . self::MAIN_COLUMN_TYPE      => $type,
                    self::MAIN_TABLE . "." . self::MAIN_COLUMN_STATUS    => self::STATUS_DRAFT,
                    self::MAIN_TABLE . "." . self::MAIN_COLUMN_PARENT_ID => null,
                ])->getPrimary();
            });

            $this->runInTransaction(function () use ($id) {
                $languages = $this->getLanguageManager()->getAvailableLanguages();

                foreach ($languages as $language) {
                    $url = $this->getFreeUrl($language);

                    $this->addLocal($language, $id, $url);

                    $this->urlUncache($url, $language->getId());

                    $localPages = [];

                    $localPages[] = $this->getByGlobalId($language->getId(), $id);

                    $this->trigger(self::TRIGGER_PAGE_ADDED, $localPages);
                }
            });
            return $id;
        });
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
     * @param bool $displayTitle
     * @param bool $displayBreadCrumbs
     * @throws Exception
     * @throws FileNotFoundById
     * @throws InvalidState
     * @throws PageNotFound
     * @throws Throwable
     */
    public function update(int $pageId, int $langId, ?int $parentId, string $title, string $description, string $url, int $globalVisibility, int $localVisibility, string $content, ?int $imageId, bool $displayTitle, bool $displayBreadCrumbs) {

        $page = $this->getPlainById($pageId, $langId);

        $identity = $this->getUser()->getIdentity();
        if (!$identity instanceof UserIdentity) throw new InvalidState("User not logged in");

        if (!in_array($localVisibility, self::STATUSES))
            throw new InvalidArgumentException("Local visiblity $localVisibility is not in " . implode("|", self::STATUSES));

        if (!in_array($globalVisibility, self::STATUSES))
            throw new InvalidArgumentException("Global visiblity $globalVisibility is not in " . implode("|", self::STATUSES));

        $parentOk = is_null($parentId);
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

        if (!preg_match("#^" . self::LOCAL_URL_CHARSET_FOR_ADMIN . "$#", $url))
            throw new Exception("URL not the right pattern: " . self::LOCAL_URL_CHARSET);

        $image = is_null($imageId) ? null : $this->getMediaManager()->getById($imageId, FileManager::TYPE_IMAGE);
        if (!is_null($imageId) && !$image instanceof Image)
            throw new InvalidArgumentException("Image not found");

        //uncache
        $this->urlUncache($page->getUrl(), $page->getLanguageId());
        $this->globalUncache($page->getGlobalId(), $page->getLanguageId());

        $newPage = $this->runInTransaction(function () use ($parentId, $page, $globalVisibility, $url, $title, $description, $content, $imageId, $localVisibility, $displayBreadCrumbs, $displayTitle) {
            if ($parentId !== $page->getParentId() || $globalVisibility !== $page->getGlobalStatus()) {

                //uncache parent
                if ($page->getParentId()) {
                    $parent = $this->getPlainById($page->getParentId(), $page->getLanguageId(), true);
                    if ($parent->getParentId()) $this->globalUncache($parent->getParentId(), $parent->getLanguageId());
                    if ($parent instanceof APage) $this->urlUncache($parent->getUrl(), $page->getParentId());
                }
                //uncache future? parent
                if ($parentId) {//!== 0 && !== null
                    $futureParent = $this->getPlainById($parentId, $page->getLanguageId(), true);
                    $this->globalUncache($parentId, $page->getLanguageId());//
                    $this->urlUncache($futureParent->getUrl(), $page->getLanguageId());
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
                    self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_URL                 => $url,
                    self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_TITLE               => $title,
                    self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_DESCRIPTION         => $description,
                    self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_CONTENT             => $content,
                    self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_IMAGE               => $imageId,
                    self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_STATUS              => $localVisibility,
                    self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_DISPLAY_TITLE       => $displayTitle,
                    self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_DISPLAY_BREADCRUMBS => $displayBreadCrumbs,
                    self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_LAST_EDITED         => new \Nette\Utils\DateTime(),
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


        //listeners to update
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
            if ($language->getHomepageId() == $globalId || $language->getErrorpageId() === $globalId)
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
        $this->trigger(self::TRIGGER_PAGE_DELETED, $globalId);
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

    /**
     * @param int $pageId
     * @param int $languageId
     * @param bool $throw
     * @return APage|null
     * @throws PageNotFound
     */
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
        return APage::factory(
            $row[self::MAIN_COLUMN_TYPE],
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
            $row[self::LOCAL_COLUMN_DISPLAY_TITLE],
            $row[self::LOCAL_COLUMN_DISPLAY_BREADCRUMBS],
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

    public function isDefaultUrl(string $url): bool {
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
            self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_STATUS              => self::STATUS_DRAFT,
            self::LOCAL_TABLE . "." . self::LOCAL_MAIN_COLUMN_ID             => $globalId,
            self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_LANG                => $language->getId(),
            self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_TITLE               => self::DEFAULT_TITLE,
            self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_URL                 => $url,
            self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_AUTHOR              => $this->getUser()->getIdentity()->getId(),
            self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_DISPLAY_TITLE       => true,
            self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_DISPLAY_BREADCRUMBS => true,
        ])->getPrimary();
    }

    public function getDefaultTitle(): string {
        return self::DEFAULT_TITLE;
    }

    private function getOrder(int $orderBy): string {
        $abs = abs($orderBy);
        $dir = $orderBy > 0 ? "ASC" : "DESC";
        return ((isset(self::ORDER_TABLE[$abs]))
                ? self::ORDER_TABLE[$abs]
                : self::ORDER_TABLE[self::ORDER_BY_ID] //default order
            ) . " " . $dir;
    }
}