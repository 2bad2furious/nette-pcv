<?php

use Nette\Database\Table\ActiveRow;

//TODO rewrite for performace gain
class PageManager extends Manager {
    const MAIN_TABLE = "page",
        MAIN_COLUMN_ID = "page_id",
        MAIN_COLUMN_TYPE = "type", /** int 1 if page 0 if post more at @var Type */
        TYPE_PAGE = 1, TYPE_POST = 0, TYPE_ALL = null,//all is just for Filtered
        TYPES = [self::TYPE_PAGE, self::TYPE_POST],
        MAIN_COLUMN_STATUS = "status", STATUS_ALL = null, STATUS_PUBLIC = 1, STATUS_DELETED = -1, STATUS_DRAFT = 0,
        STATUSES = [self::STATUS_PUBLIC, self::STATUS_DRAFT],
        MAIN_COLUMN_PARENT_ID = "parent_id",

        LOCAL_TABLE = "page_local",
        LOCAL_COLUMN_ID = "page_local_id",
        LOCAL_MAIN_COLUMN_ID = self::MAIN_COLUMN_ID,
        LOCAL_COLUMN_TITLE = "title", LOCAL_COLUMN_TITLE_LENGTH = 60,
        LOCAL_COLUMN_URL = "url", LOCAL_COLUMN_URL_LENGTH = 60, LOCAL_URL_CHARSET = "[a-zA-Z0-9_+-/]+", LOCAL_URL_CHARSET_FOR_ADMIN = "[a-zA-Z0-9_+-]+",
        LOCAL_COLUMN_DESCRIPTION = "description", LOCAL_COLUMN_DESCRIPTION_LENGTH = 255,
        LOCAL_COLUMN_IMAGE = "image",
        LOCAL_COLUMN_VIDEO = "video", LOCAL_COLUMN_VIDEO_LENGTH = 255,
        LOCAL_COLUMN_LANG = "lang_id",
        LOCAL_COLUMN_STATUS = "status",
        LOCAL_COLUMN_CONTENT = "content",
        LOCAL_COLUMN_CREATED = "created",
        LOCAL_COLUMN_LAST_EDITED = "last_edited",
        LOCAL_COLUMN_AUTHOR = "author",

        LOCAL_SEARCH = "content,title,url,description",

        CHANGES_TABLE = "page_content_change",
        CHANGE_COLUMN_ID = "change_id",
        CHANGE_LOCAL_COLUMN_ID = "page_local_id",
        CHANGE_AUTHOR_ID = "author",
        CHANGE_COLUMN_DATE = "date",
        CHANGE_COLUMN_PRE_CONTENT = "pre_content",
        CHANGE_COLUMN_AFTER_CONTENT = "after_content",

        ACTION_SEE_NON_PUBLIC_PAGES = "page.see_non_public_pages",
        ACTION_DRAFT = "page.draft",
        ACTION_MANAGE = "page.manage",
        ACTION_CACHE = "page.cache",

        PAGE_URL_PERMANENT = "permanent",
        PAGE_URL_BLACKLIST = [self::PAGE_URL_PERMANENT],

        SETTINGS_SITE_NAME = "site.title",
        SETTINGS_GOOGLE_ANALYTICS = "site.ga",
        SETTINGS_TITLE_SEPARATOR = "site.title_separator",
        SETTINGS_LOGO = "site.logo",
        SETTINGS_HOMEPAGE = "site.homepage_id",

        MESSAGE_GLOBAL_PAGE_NOT_FOUND = "admin.page.rebuild.not_in_database",
        MESSAGE_NO_GLOBAL_PAGES_TO_REBUILD = "admin.page.rebuild.no_pages",
        MESSAGE_NO_LOCAL_PAGES_FOR_GLOBAL_PAGE = "admin.page.rebuild.db_error";
    const RANDOM_URL_PREFIX = "generated-url-";

    const CACHE_GLOBAL_KEY = "global",
        CACHE_LANGUAGE_KEY = "language";

    const DEFAULT_TITLE = "admin.global.page.no_title";

    const TRIGGER_PAGE_ADDED = "trigger.page.added",
        TRIGGER_PAGE_EDITED = "trigger.page.edited",
        TRIGGER_PAGE_DELETED = "trigger.page.deleted";

    public function exists(int $globalId): bool {
        $language = $this->getLanguageManager()->getDefaultLanguage();
        return $this->getFromGlobalCache($globalId, $language) instanceof Page;
    }

    private function getLanguageTag(int $langId): string {
        return self::CACHE_LANGUAGE_KEY . "/" . $langId;
    }

    private function getGlobalTag(int $globalId): string {
        return self::CACHE_GLOBAL_KEY . "/" . $globalId;
    }

    public function delete(int $globalId) {
        $this->throwIfNoRights(self::ACTION_MANAGE);

        $this->getUrlCache()->clean([
            Cache::TAGS => [
                $this->getGlobalTag($globalId),
            ],
        ]);
        $this->getGlobalCache()->clean([
            Cache::TAGS => [
                $this->getGlobalTag($globalId),
            ]]);

        $this->getDatabase()->table(self::LOCAL_TABLE)->where([self::LOCAL_MAIN_COLUMN_ID => $globalId])->delete();
        $this->getDatabase()->table(self::MAIN_TABLE)->where([self::MAIN_COLUMN_ID => $globalId])->delete();


        $this->trigger(self::TRIGGER_PAGE_DELETED, $globalId);
    }

    protected function init() {
        LanguageManager::on(LanguageManager::TRIGGER_LANGUAGE_DELETED, function (Language $language) {
            $this->throwIfNoRights(self::ACTION_MANAGE);

            $this->getUrlCache()->clean([Cache::TAGS => $this->getLanguageTag($language->getId())]);
            $this->getGlobalCache()->clean([Cache::TAGS => $this->getLanguageTag($language->getId())]);

            $this->getDatabase()->table(self::LOCAL_TABLE)->where([
                self::LOCAL_COLUMN_LANG => $language->getId(),
            ])->delete();
        });
        parent::init();
    }


    public function rebuildCache() {
        $this->throwIfNoRights(self::ACTION_CACHE);
        $this->getUrlCache()->clean();
        $this->getGlobalCache()->clean();

        $this->rebuildGlobalUrlCache();
    }

    public static function isDefaultUrl(string $url): bool {
        return substr($url, 0, strlen(self::RANDOM_URL_PREFIX)) === self::RANDOM_URL_PREFIX;
    }

    private function rebuildGlobalUrlCache() {
        $pages = $this->getDatabase()
            ->table(self::LOCAL_TABLE)
            ->where([self::MAIN_TABLE . "." . self::MAIN_COLUMN_PARENT_ID => 0])
            ->select(self::LOCAL_COLUMN_ID);

        while ($pageRow = $pages->fetch()) {
            $localId = $pageRow[self::LOCAL_COLUMN_ID];
            $page = $this->getByLocalId($localId);
            $this->cache($page);
        }
    }

    /**
     * @param int $globalId
     * @param Language $language
     * @return array
     * @throws Exception if invalid type
     */
    public function getViableParents(int $globalId, Language $language): array {
        $page = $this->getFromGlobalCache($globalId, $language);
        if ($page->isPage()) {
            $allowedTypes = [self::TYPE_PAGE];
        } else throw new Exception("Invalid Type");

        $invalidGlobalIds = $this->getInvalidParents($globalId);

        $pages = $this->getDatabase()->table(self::LOCAL_TABLE)->where(
            [self::LOCAL_TABLE . "." . self::LOCAL_MAIN_COLUMN_ID . " NOT ?" => $invalidGlobalIds,
             self::LOCAL_COLUMN_LANG                                         => $language->getId(),
             self::MAIN_TABLE . "." . self::MAIN_COLUMN_TYPE                 => $allowedTypes]
        )->fetchAll();
        $parents = [];
        /** @var ActiveRow $parent */
        foreach ($pages as $parent) {
            $parents[$parent[self::LOCAL_MAIN_COLUMN_ID]] = $parent[self::LOCAL_COLUMN_TITLE];
        }
        return $parents;
    }

    /**
     * @param int $globalId
     * @return array globalIds of the parents
     */
    private function getInvalidParents(int $globalId): array {
        $invalidParents = [$globalId];

        $children = $this->getDatabase()->table(self::MAIN_TABLE)->where([self::MAIN_COLUMN_PARENT_ID => $globalId])->select(self::MAIN_COLUMN_ID)->fetchAll();
        /** @var ActiveRow $parent */
        foreach ($children as $parent) {
            $invalidParents = array_merge($invalidParents, $this->getInvalidParents($parent[self::MAIN_COLUMN_ID]));
        }
        return $invalidParents;
    }

    /**
     * @param int $type (page|post)
     * @return int ID of the page added
     * @throws Exception
     */
    public function addEmpty(int $type): int {
        $this->throwIfNoRights(self::ACTION_MANAGE);

        if (!in_array($type, self::TYPES)) throw new InvalidArgumentException("Invalid type " . implode("|", self::TYPES) . " needed, $type got.");

        $globalId = $this->getDatabase()->table(self::MAIN_TABLE)->insert([
            self::MAIN_COLUMN_TYPE      => $type,
            self::MAIN_COLUMN_STATUS    => self::STATUS_DRAFT,
            self::MAIN_COLUMN_PARENT_ID => 0,
        ])->getPrimary();

        /** @var Language[] $languages */
        $languages = $this->getLanguageManager()->getAvailableLanguages(true);

        $localIds = [];

        foreach ($languages as $language) {
            $url = $this->getFreeUrl($language);

            $localIds[] = $this->getDatabase()->table(self::LOCAL_TABLE)->insert([
                self::LOCAL_COLUMN_STATUS  => self::STATUS_DRAFT,
                self::LOCAL_MAIN_COLUMN_ID => $globalId,
                self::LOCAL_COLUMN_LANG    => $language->getId(),
                self::LOCAL_COLUMN_TITLE   => self::DEFAULT_TITLE,
                self::LOCAL_COLUMN_URL     => $url,
                self::LOCAL_COLUMN_AUTHOR  => $this->getUser()->getIdentity()->getId(),
            ])->getPrimary();
        }

        $localPages = [];

        foreach ($localIds as $localId) {
            $this->cache($localPages[] = $this->getByLocalId($localId));
        }
        $this->trigger(self::TRIGGER_PAGE_ADDED, $localPages);
        return $globalId;
    }

    private function getByLocalId(int $localId): Page {
        $localRow = $this->getDatabase()->table(self::LOCAL_TABLE)->get($localId);
        if (!$localRow instanceof ActiveRow) throw new InvalidStateOfDB("row not found");

        $language = $this->getLanguageManager()->getById($localRow[self::LOCAL_COLUMN_LANG]);
        if (!$language instanceof Language) {
            throw new InvalidStateOfDB("Page with unknown language. localId:" . $localRow[self::LOCAL_COLUMN_ID] . " langId:" . $localRow[self::LOCAL_COLUMN_LANG]);
        }

        $globalRow = $this->getDatabase()->table(self::MAIN_TABLE)->get($globalId = $localRow[self::LOCAL_MAIN_COLUMN_ID]);
        $type = Type::getById($globalRow[self::MAIN_COLUMN_TYPE]);
        if (!$type instanceof Type) throw new InvalidStateOfDB("Type not found");

        $image = $this->getMediaManager()->getById($imageId = $localRow[self::LOCAL_COLUMN_IMAGE]);
        if ($image instanceof Media && !$image->isImage()) throw new InvalidStateOfDB("Media not image");
        if ($imageId !== 0 && !$image instanceof Media) throw new InvalidStateOfDB("Media not found");

        return new Page(
            $globalId,
            $localId,
            $localRow[self::LOCAL_COLUMN_TITLE],
            $localRow[self::LOCAL_COLUMN_DESCRIPTION],
            $localRow[self::LOCAL_COLUMN_URL],
            $imageId,
            $type,
            $localRow[self::LOCAL_COLUMN_CONTENT],
            $localRow[self::LOCAL_COLUMN_AUTHOR],
            $localRow[self::LOCAL_COLUMN_CREATED],
            $localRow[self::LOCAL_COLUMN_LAST_EDITED],
            $globalRow[self::MAIN_COLUMN_STATUS],
            $localRow[self::LOCAL_COLUMN_STATUS],
            $language->getId(),
            $globalRow[self::MAIN_COLUMN_PARENT_ID]
        );
    }

    /**
     * @param Language $language
     * @param int $id PageId
     * @return null|Page
     */
    public function getByGlobalId(Language $language, int $id): ?Page {
        $cached = $this->getFromGlobalCache($id, $language);
        return $cached instanceof Page ? $this->getIfRightsAndSetNotCached($cached) : null;
    }

    /**
     * @param Language $language
     * @param string $url
     * @return null|Page
     */
    public function getByUrl(Language $language, string $url):?Page {
        $cached = $this->getFromUrlCache($url, $language);
        return $cached instanceof Page ? $this->getIfRightsAndSetNotCached($cached) : null;
    }

    /**
     * @param Page $page
     * @return null|Page
     */
    private function getIfRightsAndSetNotCached(Page $page):?Page {
        if ($page->getStatus() == 1 || $this->hasRightsToSeeNonPublicPages()) {

            $page->setLanguage($this->getLanguageManager()->getById($page->getLanguageId()));

            if (($parentId = $page->getParentId()) !== 0)
                $page->setParent($this->getFromGlobalCache($parentId, $page->getLang()));

            $page->setPageSettings($this->getSettingsManager()->getPageSettings($page->getLang()));

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

    private function hasRightsToSeeNonPublicPages(): bool {
        return $this->getUser()->isAllowed(self::ACTION_SEE_NON_PUBLIC_PAGES);
    }


    /**
     * @param int|null $type null when ALL
     * @param int $visibility
     * @param Language|null $language null if all languages, specific language if translation missing or looking for
     * @param bool $hasTranslation
     * @param int $page
     * @param int $perPage
     * @param $numOfPages
     * @param null|string $search
     * @return array => [id=>[titles => "" ,type=>"",languages=>[LangObj,...]]]
     * @throws InvalidStateOfDB
     */
    public function getFiltered(?int $type = null, ?int $visibility = null, ?Language $language, ?bool $hasTranslation, int $page, int $perPage, &$numOfPages, ?string $search) {
        //TODO get localIds and return array of pages instead of this ugly-ass title and lang_id stuff
        $selection = $this->getDatabase()->table(self::LOCAL_TABLE)->select(self::LOCAL_TABLE . "." . self::LOCAL_MAIN_COLUMN_ID)->group(self::LOCAL_TABLE . "." . self::LOCAL_MAIN_COLUMN_ID)->select("GROUP_CONCAT(" . self::LOCAL_COLUMN_LANG . " SEPARATOR '|') langs")->select("GROUP_CONCAT(" . self::LOCAL_COLUMN_TITLE . " SEPARATOR ', ') titles")->select(self::MAIN_TABLE . "." . self::MAIN_COLUMN_TYPE)->order(self::LOCAL_TABLE . "." . self::LOCAL_MAIN_COLUMN_ID);

        if (is_int($type)) $selection = $selection->where(self::MAIN_TABLE . "." . self::MAIN_COLUMN_TYPE, $type);

        if (is_string($search)) {
            $searchWheres = [];
            $values = [];
            $explosion = explode(" ", $search);
            foreach ($explosion as $item) {
                if (($possibleId = intval($item)) !== 0) {
                    $searchWheres[] = self::LOCAL_TABLE . "." . self::LOCAL_MAIN_COLUMN_ID . " = ?";
                    $values[] = $possibleId;
                }
                $searchWheres[] = "MATCH(" . self::LOCAL_SEARCH . ") AGAINST (?)";
                $values[] = $item;
            }
            if (count($searchWheres) === 1) {
                $values = $search;
            }
            $selection = $selection->where("(" . implode(") OR (", $searchWheres) . ")", $values);
        }

        if ($language instanceof Language && is_bool($hasTranslation)) {
            $selection = $selection->where(
                [self::LOCAL_COLUMN_CONTENT . ($hasTranslation ? " != ?" : "") => "",
                 self::LOCAL_COLUMN_LANG                                       => $language->getId(),]
            );
        }

        $leastVisibility = "LEAST(" . self::MAIN_TABLE . "." . self::MAIN_COLUMN_STATUS . "," . self::LOCAL_TABLE . "." . self::LOCAL_COLUMN_STATUS . ")";
        /* only some visibility vs all */
        $selection = is_int($visibility) ?
            $selection->where([$leastVisibility => $visibility]) :
            $selection->where($leastVisibility . " >= ?", self::STATUS_DELETED); //TODO fix deletion

        $result = $selection->page($page, $perPage, $numOfPages)->fetchAll();

        $pages = [];
        /** @var ActiveRow $item */
        foreach ($result as $item) { //TODO display title in current language or in default
            $languages = array_map(function (int $langId): Language {
                return $this->getLanguageManager()->getById($langId);
            }, explode("|", $item['langs']));
            $pages[$item[self::LOCAL_MAIN_COLUMN_ID]] = ['titles' => implode(", ", array_map(function (string $title) {
                return ($title !== self::DEFAULT_TITLE) ? $title : $this->getTranslator()->translate($title);

            }, explode(", ", $item['titles']))), 'type'           => Type::getById($item[self::MAIN_COLUMN_TYPE])->__toString(), 'languages' => $languages];
        }
        return $pages;

    }


    public function getDefault404(): Page {
        //TODO dont translate here?
        $translator = $this->getTranslator();

        $lang = $this->getLanguageManager()->getByCode($translator->getLocale());
        if (!$lang instanceof Language) throw new Exception("invalid Language in translator");
        $page = new Page(
            Page::ID_404,
            Page::ID_404,
            $translator->translate("page.default.404.title"),
            $translator->translate("page.default.404.description"),
            "",
            0,
            Type::getByID(self::TYPE_PAGE),
            $translator->translate("page.default.404.content"),
            0,
            new \Nette\Utils\DateTime(),
            new \Nette\Utils\DateTime(),
            1, 1,
            $lang->getId(),
            0
        );
        return $this->getIfRightsAndSetNotCached($page);
    }

    private function getUrlCacheKey(string $url, int $language) {
        return $language . "_" . $url;
    }

    private function getGlobalCacheKey(int $id, int $language) {
        return $language . "_" . $id;
    }

    /**
     * @param string $url
     * @param int|null $localId
     * @return bool
     */
    public function isUrlAvailable(string $url, Language $language, ?int $localId): bool {
        $selection = $this->getDatabase()->table(self::LOCAL_TABLE)->where([
            self::LOCAL_COLUMN_URL  => $url,
            self::LOCAL_COLUMN_LANG => $language->getId(),
        ]);
        if (is_int($localId)) $selection->where([
            self::LOCAL_COLUMN_ID . " !=" => $localId,
        ]);
        return !$selection->count();
    }

    public function update(Page $page, int $parentId, string $title, string $description, string $url, int $globalVisibility, int $localVisibility, string $content, int $imageId) {

        $this->throwIfNoRights(self::ACTION_MANAGE);

        if (!in_array($localVisibility, self::STATUSES)) throw new Exception("Local visiblity $localVisibility is not in " . implode("|", self::STATUSES));

        if (!in_array($globalVisibility, self::STATUSES)) throw new Exception("Global visiblity $globalVisibility is not in " . implode("|", self::STATUSES));

        if (in_array($parentId, $this->getInvalidParents($page->getGlobalId()))) throw new Exception("Parent is invalid");

        $parent = $parentId === 0 ? null : $this->getFromGlobalCache($parentId, $page->getLang());
        if ($parentId !== 0 && !$parent instanceof Page) throw new InvalidStateOfDB("Parent not found");

        dump($url, "#" . self::LOCAL_URL_CHARSET_FOR_ADMIN . "#");
        dump(preg_match("#" . self::LOCAL_URL_CHARSET_FOR_ADMIN . "#", $url));
        if (!preg_match("#" . self::LOCAL_URL_CHARSET_FOR_ADMIN . "#", $url)) throw new Exception("URL not the right pattern: " . self::LOCAL_URL_CHARSET);

        $image = $imageId === 0 ? null : $this->getMediaManager()->getById($imageId);
        if ($imageId !== 0 && !$image instanceof Media) throw new InvalidStateOfDB("Image not found");
        if ($image instanceof Media && !$image->isImage()) throw new Exception("Not an image");

        if ($parentId !== $page->getParentId() || $globalVisibility !== $page->getGlobalStatus()) {
            $this->getDatabase()
                ->table(self::MAIN_TABLE)
                ->where([
                    self::MAIN_COLUMN_ID => $page->getGlobalId(),
                ])
                ->update([
                    self::MAIN_COLUMN_PARENT_ID => $parentId,
                    self::MAIN_COLUMN_STATUS    => $globalVisibility,
                ]);
        }

        $this->getDatabase()
            ->table(self::LOCAL_TABLE)
            ->where([
                self::LOCAL_COLUMN_ID => $page->getLocalId(),
            ])
            ->update([
                self::LOCAL_COLUMN_URL         => $url,
                self::LOCAL_COLUMN_TITLE       => $title,
                self::LOCAL_COLUMN_DESCRIPTION => $description,
                self::LOCAL_COLUMN_CONTENT     => $content,
                self::LOCAL_COLUMN_IMAGE       => $image,
                self::LOCAL_COLUMN_STATUS      => $localVisibility,
                self::LOCAL_COLUMN_LAST_EDITED => new \Nette\Utils\DateTime(),
            ]);

        $newPage = $this->getByLocalId($page->getLocalId());
        if (!$newPage instanceof Page) throw new InvalidStateOfDB("Page not found");

        $this->cache($newPage);

        $identity = $this->getUser()->getIdentity();
        if (!$identity instanceof UserIdentity) throw new Exception("User not logged in");

        $this->getDatabase()->table(self::CHANGES_TABLE)->insert([
            self::CHANGE_LOCAL_COLUMN_ID      => $page->getLocalId(),
            self::CHANGE_AUTHOR_ID            => $this->getUser()->getId(),
            self::CHANGE_COLUMN_PRE_CONTENT   => $page->getContent(),
            self::CHANGE_COLUMN_AFTER_CONTENT => $newPage->getContent(),
        ]);

        //force header and other shit to update
        $this->trigger(self::TRIGGER_PAGE_EDITED, $page);
    }

    private function getFromUrlCache(string $url, Language $language):?Page {
        return $this->getUrlCache()->load($this->getUrlCacheKey($url, $language->getId()));
    }

    private function getFromGlobalCache(int $id, Language $language):?Page {
        return $this->getGlobalCache()->load($this->getGlobalCacheKey($id, $language->getId()));
    }

    private function cache(Page $page) {
        $urlKey = $this->getUrlCacheKey($page->getUrl(), $page->getLanguageId());
        $globalKey = $this->getGlobalCacheKey($page->getGlobalId(), $page->getLanguageId());
        $this->getUrlCache()->save($urlKey, $page, [
            Cache::TAGS => [
                $this->getGlobalTag($page->getGlobalId()),
                $this->getLanguageTag($page->getLanguageId()),
            ],
        ]);
        $this->getGlobalCache()->save($globalKey, $page, [
            Cache::TAGS => [
                $this->getGlobalTag($page->getGlobalId()),
                $this->getLanguageTag($page->getLanguageId()),
            ],
        ]);
    }

    private function getFreeUrl(Language $language): string {
        $unique = self::RANDOM_URL_PREFIX . sha1(uniqid());

        if (!$this->getDatabase()->table(self::LOCAL_TABLE)->where([self::LOCAL_COLUMN_URL => $unique, self::LOCAL_COLUMN_LANG => $language->getId()])->fetch()) return $unique; /* if !exists */

        return $this->getFreeUrl($language);
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
}