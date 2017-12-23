<?php


use Nette\Database\IRow;

class PageManager extends Manager implements IPageManager {
    const MAIN_TABLE = "page",
        MAIN_COLUMN_ID = "page_id",
        MAIN_COLUMN_TYPE = "type", /** int 1 if page 0 if post more at @var Type */
        TYPE_PAGE = 1, TYPE_POST = 0, TYPE_ALL = null,//all is just for Filtered
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

        LOCAL_SEARCH = self::LOCAL_TABLE . "." . "content," . self::LOCAL_TABLE . "." . "title," . self::LOCAL_TABLE . "." . "url," . self::LOCAL_TABLE . "." . "description",

        CHANGES_TABLE = "page_content_change",
        CHANGE_COLUMN_ID = "change_id",
        CHANGE_LOCAL_COLUMN_ID = "page_local_id",
        CHANGE_AUTHOR_ID = "author",
        CHANGE_COLUMN_DATE = "date",
        CHANGE_COLUMN_PRE_CONTENT = "pre_content",
        CHANGE_COLUMN_AFTER_CONTENT = "after_content";

    const RANDOM_URL_PREFIX = "generated-url-";

    public function exists(int $globalId): bool {
        return $this->getDatabase()->table(self::MAIN_TABLE)
                ->where([self::MAIN_TABLE . "." . self::MAIN_COLUMN_ID => $globalId])
                ->fetch() instanceof IRow;
    }

    public function getAllPages(Language $language, $asObjects = true): array {
        $data = $this->getDatabase()->table(self::LOCAL_TABLE)
            ->where([self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_LANG => $language->getId()]);

        $pages = [];
        while ($row = $data->fetch()) {
            $pages[] = $this->getByGlobalId($language, $row[self::LOCAL_MAIN_COLUMN_ID]);
        }
        return $pages;
    }

    /**
     * @param int $globalId
     * @param Language $language
     * @return Page[]
     * @throws InvalidArgumentException if invalid type or page not found
     */
    public function getViableParents(int $globalId, Language $language): array {
        $page = $this->getByGlobalId($language, $globalId);
        if (!$page instanceof Page) throw new InvalidArgumentException("Page not found");
        else if ($page->isPage()) {
            $allowedTypes = [self::TYPE_PAGE];
        } else throw new InvalidArgumentException("Invalid Type");

        $invalidGlobalIds = $this->getInvalidParents($globalId);

        $pages = $this->getDatabase()->table(self::LOCAL_TABLE)->where(
            [self::LOCAL_TABLE . "." . self::LOCAL_MAIN_COLUMN_ID . " NOT ?" => $invalidGlobalIds,
             self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_LANG               => $language->getId(),
             self::MAIN_TABLE . "." . self::MAIN_COLUMN_TYPE                 => $allowedTypes]
        );

        $parents = [];
        while ($parent = $pages->fetch()) {
            $parents[$parent[self::LOCAL_MAIN_COLUMN_ID]] = $parent[self::LOCAL_COLUMN_TITLE];
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
     * @return Page[]
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
                dump($values);
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
        $this->getCache()->clean(); //TODO check if cleaning separately needed
    }

    public function getHomePage(Language $language): ?Page {
        $homePageSetting = $this->getSettingsManager()->get(self::SETTINGS_HOMEPAGE, $language);
        if (!$homePageSetting instanceof Setting) {
            trigger_error("HomePage setting not found, setting to none.");
            $homePageSetting = $this->getSettingsManager()->set(self::SETTINGS_HOMEPAGE, 0, $language);
        }
        $homePageId = (int)$homePageSetting->getValue();
        $homePage = $this->getByGlobalId($language, $homePageId);
        if ($homePageId !== 0 && !$homePage instanceof Page) {
            trigger_error("HomePage $homePageId not found, setting to none.");
            $this->getSettingsManager()->set(self::SETTINGS_HOMEPAGE, 0, $language);

            return $this->getHomePage($language);
        }
        return $homePage;
    }

    /**
     * @param Language $language
     * @param int $id PageId
     * @return null|Page
     */
    public function getByGlobalId(Language $language, int $id): ?Page {
        $cached = $this->getGlobalCache()->load($this->getGlobalCacheKey($id, $language), function (&$dependencies) use ($language, $id) {
            $dependencies = self::getDependencies($id, $language);
            return $this->getFromDbByGlobalId($id, $language);
        });

        return $cached instanceof Page ? $this->getIfRightsAndSetMissing($cached) : null;
    }

    /**
     * @param Language $language
     * @param string $url
     * @return null|Page
     */
    public function getByUrl(Language $language, string $url): ?Page {
        $cached = $this->getUrlCache()->load($this->getUrlCacheKey($url, $language), function (&$dependencies) use ($language, $url) {
            $page = $this->getFromDbByUrl($url, $language);
            $dependencies = self::getDependencies($page instanceof Page ? $page->getGlobalId() : -1, $language);
            return $page;
        });

        return $cached instanceof Page ? $this->getIfRightsAndSetMissing($cached) : null;
    }

    private function getIfRightsAndSetMissing(Page $page) {
        if ($page->getStatus() == 1 || $this->getUser()->isAllowed(self::ACTION_SEE_NON_PUBLIC_PAGES)) {

            $page->setLanguage($language = $this->getLanguageManager()->getById($page->getLanguageId()));

            if (($parentId = $page->getParentId()) !== 0)
                $page->setParent($this->getByGlobalId($language, $parentId));

            $page->setPageSettings($this->getSettingsManager()->getPageSettings($language));

            if (($imageId = $page->getImageId()) !== 0) {
                $image = $this->getMediaManager()->getById($imageId);
                if (!$image->isImage())
                    $page->setImage($image);
            }

            if (($userId = $page->getAuthorId()) !== 0)
                $page->setAuthor($this->getUserManager()->getUserIdentityById($userId));

            return $page;
        }
        return null;
    }

    public function getDefault404(): Page {
        //TODO implement
    }

    /**
     * @param string $url
     * @param Language $language
     * @param int|null $localId
     * @return bool
     */
    public function isUrlAvailable(string $url, Language $language, ?int $localId): bool {
        $data = $this->getDatabase()->table(self::LOCAL_TABLE)
            ->where([
                self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_LANG => $language->getId(),
                self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_URL  => $url,
            ]);
        if (is_int($localId))
            $data = $data->where([self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_ID . " != " => $localId]);
        dump($data->getSql(), $data->fetch(), $url, $language, $localId);
        return !$data->fetch() instanceof IRow;
    }

    /**
     * @param int $type (page|post)
     * @return int ID of the page added
     * @throws InvalidArgumentException|Exception
     */
    public function addEmpty(int $type): int {
        if (!in_array($type, self::TYPES)) throw new InvalidArgumentException("Invalid type " . implode("|", self::TYPES) . " needed, $type got.");

        if (!($inTransaction = $this->getDatabase()->getConnection()->getPdo()->inTransaction()))
            $this->getDatabase()->beginTransaction();

        try {
            $globalId = $this->getDatabase()->table(self::MAIN_TABLE)->insert([
                self::MAIN_TABLE . "." . self::MAIN_COLUMN_TYPE      => $type,
                self::MAIN_TABLE . "." . self::MAIN_COLUMN_STATUS    => self::STATUS_DRAFT,
                self::MAIN_TABLE . "." . self::MAIN_COLUMN_PARENT_ID => 0,
            ])->getPrimary();

            $languages = $this->getLanguageManager()->getAvailableLanguages(true);

            foreach ($languages as $language) {
                $url = $this->getFreeUrl($language);

                $this->getDatabase()->table(self::LOCAL_TABLE)->insert([
                    self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_STATUS  => self::STATUS_DRAFT,
                    self::LOCAL_TABLE . "." . self::LOCAL_MAIN_COLUMN_ID => $globalId,
                    self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_LANG    => $language->getId(),
                    self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_TITLE   => self::DEFAULT_TITLE,
                    self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_URL     => $url,
                    self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_AUTHOR  => $this->getUser()->getIdentity()->getId(),
                ])->getPrimary();

                $this->urlUncache($url, $language);
            }
            if (!$inTransaction) $this->getDatabase()->commit();
        } catch (Exception $exception) {
            if (!$inTransaction) $this->getDatabase()->rollBack();
            throw $exception;
        }

        $localPages = [];

        foreach ($languages as $language) {
            $localPages[] = $this->getByGlobalId($language, $globalId);
        }

        $this->trigger(self::TRIGGER_PAGE_ADDED, $localPages);
        return $globalId;
    }

    public function update(Page $page, ?int $parentId, string $title, string $description, string $url, int $globalVisibility, int $localVisibility, string $content, int $imageId) {

        $identity = $this->getUser()->getIdentity();
        if (!$identity instanceof UserIdentity) throw new InvalidState("User not logged in");

        if (!in_array($localVisibility, self::STATUSES))
            throw new InvalidArgumentException("Local visiblity $localVisibility is not in " . implode("|", self::STATUSES));

        if (!in_array($globalVisibility, self::STATUSES))
            throw new InvalidArgumentException("Global visiblity $globalVisibility is not in " . implode("|", self::STATUSES));

        $parentOk = $parentId === 0 || is_null($parentId);
        if (!$parentOk)
            foreach ($this->getViableParents($page->getGlobalId(), $page->getLang()) as $possibleParent) {
                if ($possibleParent->getGlobalId() === $parentId) {
                    $parentOk = true;
                    break;
                }
            }

        if (!$parentOk) {
            if (!$this->getByGlobalId($page->getLang(), $parentId) instanceof Page)
                throw new InvalidArgumentException("Parent does not exist");

            if (in_array($parentId, $this->getInvalidParents($page->getGlobalId())))
                throw new InvalidArgumentException("Parent is invalid - descendant");
        }

        if (!preg_match("#" . self::LOCAL_URL_CHARSET_FOR_ADMIN . "#", $url))
            throw new Exception("URL not the right pattern: " . self::LOCAL_URL_CHARSET);

        $image = $imageId === 0 ? null : $this->getMediaManager()->getById($imageId, MediaManager::TYPE_IMAGE);
        if ($imageId !== 0 && !$image instanceof Media)
            throw new InvalidArgumentException("Image not found");

        if (!($inTransaction = $this->getDatabase()->getConnection()->getPdo()->inTransaction()))
            $this->getDatabase()->beginTransaction();
        dump($inTransaction, $content);
        try {
            if ($parentId !== $page->getParentId() || $globalVisibility !== $page->getGlobalStatus()) {
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


            $this->urlUncache($page->getUrl(), $page->getLang());
            $this->globalUncache($page->getGlobalId(), $page->getLang());
            $newPage = $this->getByGlobalId($page->getLang(), $page->getGlobalId());

            if ($content !== $page->getContent()) {
                $this->getDatabase()->table(self::CHANGES_TABLE)->insert([
                    self::CHANGE_LOCAL_COLUMN_ID      => $page->getLocalId(),
                    self::CHANGE_AUTHOR_ID            => $this->getUser()->getId(),
                    self::CHANGE_COLUMN_PRE_CONTENT   => $page->getContent(),
                    self::CHANGE_COLUMN_AFTER_CONTENT => $newPage->getContent(),
                ]);
            }

            if (!$inTransaction) $this->getDatabase()->commit();
        } catch (Exception $ex) {
            if (!$inTransaction) $this->getDatabase()->rollBack();
            $this->urlUncache($page->getUrl(), $page->getLang());
            $this->globalUncache($page->getGlobalId(), $page->getLang());
            throw $ex;
        }

        //force header and other shit to update
        $this->trigger(self::TRIGGER_PAGE_EDITED, $newPage);
    }

    /**
     * @param int $globalId
     * @throws InvalidArgumentException|Exception
     */
    public function delete(int $globalId) {
        if (!$this->exists($globalId)) throw new InvalidArgumentException("Page not found");

        $this->getGlobalCache()->clean($tags = [Cache::TAGS => [
            self::getGlobalTag($globalId),
        ]]);
        $this->getUrlCache()->clean($tags);

        if (!($inTransaction = $this->getDatabase()->getConnection()->getPdo()->inTransaction()))
            $this->getDatabase()->beginTransaction();
        try {
            $this->getDatabase()->table(self::LOCAL_TABLE)
                ->where([self::LOCAL_TABLE . "." . self::LOCAL_MAIN_COLUMN_ID => $globalId])->delete();
            $this->getDatabase()->table(self::MAIN_TABLE)
                ->where([self::MAIN_COLUMN_ID => $globalId])->delete();

            if (!$inTransaction) $this->getDatabase()->commit();
        } catch (Exception $exception) {
            if (!$inTransaction) $this->getDatabase()->rollBack();
            throw $exception;
        }
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

    private function getGlobalCacheKey(int $id, Language $language) {
        return $language->getId() . "\$" . $id;
    }

    private function getUrlCacheKey(string $url, Language $language) {
        return $language->getId() . "\$" . $url;
    }

    private static function getDependencies(int $globalId, Language $language): array {
        return array_merge(self::getTags($globalId, $language));
    }

    private static function getTags(int $globalId, Language $language) {
        return [Cache::TAGS => [
            self::getGlobalTag($globalId),
            self::getLanguageTag($language),
        ]];
    }

    private static function getGlobalTag(int $globalId) {
        return "global_" . $globalId;
    }

    private static function getLanguageTag(Language $language) {
        return "language_" . $language->getId();
    }

    /**
     * @param int $id
     * @param Language $language
     * @return false|Page
     */
    private function getFromDbByGlobalId(int $id, Language $language) {
        return $this->getFromDb([
            self::LOCAL_TABLE . "." . self::LOCAL_MAIN_COLUMN_ID => $id,
            self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_LANG    => $language->getId(),
        ]);
    }

    /**
     * @param string $url
     * @param Language $language
     * @return false|Page
     */
    private function getFromDbByUrl(string $url, Language $language) {
        return $this->getFromDb([
            self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_LANG => $language->getId(),
            self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_URL  => $url,
        ]);
    }

    /**
     * @param array $where
     * @return false|Page
     */
    private function getFromDb(array $where) {
        $data = $this->getDatabase()->table(self::LOCAL_TABLE)
            ->where($where)
            ->select(self::MAIN_TABLE . ".*")
            ->select(self::LOCAL_TABLE . ".*")
            ->fetch();

        return $data instanceof IRow ? $this->createFromRow($data) : false;
    }

    private function createFromRow(IRow $row): Page {
        return new Page(
            $row[self::MAIN_COLUMN_ID],
            $row[self::LOCAL_COLUMN_ID],
            $row[self::LOCAL_COLUMN_TITLE],
            $row[self::LOCAL_COLUMN_DESCRIPTION],
            $row[self::LOCAL_COLUMN_URL],
            $row[self::LOCAL_COLUMN_IMAGE],
            Type::getById($row[self::MAIN_COLUMN_TYPE]),
            $row[self::LOCAL_COLUMN_CONTENT],
            $row[self::LOCAL_COLUMN_AUTHOR],
            $row[self::LOCAL_COLUMN_CREATED],
            $row[self::LOCAL_COLUMN_LAST_EDITED],
            $row[self::MAIN_COLUMN_STATUS],
            $row[self::LOCAL_COLUMN_STATUS],
            $row[self::LOCAL_COLUMN_LANG],
            $row[self::MAIN_COLUMN_PARENT_ID]
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

    private function urlUncache(string $url, Language $language) {
        $this->getUrlCache()->remove($this->getUrlCacheKey($url, $language));
    }

    private function globalUncache(int $globalId, Language $lang) {
        $this->getGlobalCache()->remove($this->getGlobalCacheKey($globalId, $lang));
        $this->getGlobalCache()->clean($tags = self::getTags($globalId, $lang));
        $this->getUrlCache()->clean($tags);
    }

    /**
     * @param int[] $localIds
     * @return Page[]
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

            $lang = $this->getLanguageManager()->getById($data[self::LOCAL_COLUMN_LANG]);
            $globalId = $data[self::LOCAL_MAIN_COLUMN_ID];
            $pages[$localId] = $this->getByGlobalId($lang, $globalId);
        }
        return $pages;
    }

    public static function isDefaultUrl(string $url): bool {
        return substr($url, 0, strlen(self::RANDOM_URL_PREFIX)) === self::RANDOM_URL_PREFIX;
    }
}