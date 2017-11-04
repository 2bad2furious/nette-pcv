<?php

use Kdyby\Translation\Translator;
use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Database\Context;
use Nette\Database\Table\ActiveRow;
use Nette\DI\Container;
use Nette\Security\User;

//TODO rewrite for performace gain
class PageManager {
    const MAIN_TABLE = "page",
        MAIN_COLUMN_ID = "page_id",
        MAIN_COLUMN_TYPE = "type", /** int 1 if page 0 if post, -1 if section more at @var Type */
        TYPE_SECTION = -1, TYPE_PAGE = 1, TYPE_POST = 0, TYPE_ALL = null,
        TYPES = [self::TYPE_SECTION, self::TYPE_PAGE, self::TYPE_POST],
        MAIN_COLUMN_STATUS = "status", STATUS_ALL = null, STATUS_PUBLIC = 1, STATUS_DELETED = -1, STATUS_DRAFT = 0,
        MAIN_COLUMN_PARENT_ID = "parent_id",

        LOCAL_TABLE = "page_local",
        LOCAL_COLUMN_ID = "page_local_id",
        LOCAL_MAIN_COLUMN_ID = self::MAIN_COLUMN_ID,
        LOCAL_COLUMN_TITLE = "title", LOCAL_COLUMN_TITLE_LENGTH = 60,
        LOCAL_COLUMN_URL = "url", LOCAL_COLUMN_URL_LENGTH = 60, LOCAL_URL_CHARSET = "[a-zA-Z0-9_+-/]+", LOCAL_URL_CHARSET_FOR_ADMIN = "[a-zA-Z0-9_+-]+",
        LOCAL_COLUMN_DESCRIPTION = "description", LOCAL_COLUMN_DESCRIPTION_LENGTH = 255,
        LOCAL_COLUMN_IMAGE = "image", LOCAL_COLUMN_IMAGE_LENGTH = 255,
        LOCAL_COLUMN_IMAGE_ALT = "image_alt", LOCAL_COLUMN_IMAGE_ALT_LENGTH = 255,
        LOCAL_COLUMN_VIDEO = "video", LOCAL_COLUMN_VIDEO_LENGTH = 255,
        LOCAL_COLUMN_LANG = "lang_id",
        LOCAL_COLUMN_STATUS = "status",
        LOCAL_COLUMN_CONTENT = "content",
        LOCAL_COLUMN_CREATED = "created",
        LOCAL_COLUMN_LAST_EDITED = "last_edited",
        LOCAL_COLUMN_AUTHOR = "author",

        LOCAL_SEARCH = "content,title,url,description",

        CHANGES_TABLE = "page_change",
        CHANGE_COLUMN_ID = "change_id",
        CHANGES_COLUMN_PAGE_LOCAL_ID = "page_local_id",
        CHANGES_COLUMN_CREATED = "created",
        CHANGES_COLUMN_AUTHOR = "author_id",
        CHANGES_COLUMN_VERIFIED = "verified",
        CHANGES_COLUMN_VERIFICATOR = "verified_by",
        CHANGES_COLUMN_DELETED = "deleted",
        CHANGES_COLUMN_DELETED_BY = "deleted_by",
        CHANGES_COLUMN_PRE_CHANGE = "page_pre_change",
        CHANGES_COLUMN_POST_CHANGE = "page_post_change",

        ACTION_SEE_NON_PUBLIC_PAGES = "page.see_non_public_pages",
        ACTION_DRAFT = "page.draft",
        ACTION_MANAGE = "page.manage",

        PAGE_URL_PERMANENT = "permanent",
        PAGE_URL_BLACKLIST = [self::PAGE_URL_PERMANENT],

        SETTINGS_SITE_NAME = "site.title",
        SETTINGS_GOOGLE_ANALYTICS = "site.ga",
        SETTINGS_TITLE_SEPARATOR = "site.title_separator",
        SETTINGS_LOGO = "site.logo",
        SETTINGS_LOGO_ALT = "site.logo.alt",

        MESSAGE_GLOBAL_PAGE_NOT_FOUND = "admin.page.rebuild.not_in_database",
        MESSAGE_NO_GLOBAL_PAGES_TO_REBUILD = "admin.page.rebuild.no_pages",
        MESSAGE_NO_LOCAL_PAGES_FOR_GLOBAL_PAGE = "admin.page.rebuild.db_error";
    const RANDOM_URL_PREFIX = "$#/\/prefix$#/\/";

    /**
     * @var Context
     */
    protected $database;
    /**
     * @var User
     */
    private $user;
    /**
     * @var LanguageManager
     */
    private $languageManager;
    /**
     * @var SettingsManager
     */
    private $settingsManager;
    /**
     * @var UserManager
     */
    private $userManager;
    /**
     * @var TagManager
     */
    private $tagManager;
    /**
     * @var Cache
     */
    private $urlCache;
    /**
     * @var Cache
     */
    private $globalCache;
    /**
     * @var Container
     */
    private $context;
    /**
     * @var HeaderManager
     */
    private $headerManager;
    /**
     * @var Cache
     */
    private $sectionCache;

    /**
     * PageManager constructor.
     * @param IStorage $storage
     * @param Container $context
     */
    public function __construct(IStorage $storage, Container $context) {
        $cache = new Cache($storage, "page");
        $this->urlCache = $cache->derive("url");
        $this->globalCache = $cache->derive("global");
        $this->sectionCache = $cache->derive("section");
        $this->context = $context;
    }


    /**
     * Rebuilds whole cache or just part if page is specified
     * @param null|Page $page
     * @return array
     */
    public function rebuildCache(?Page $page = null): array {
        $globalUrlMessages = $this->rebuildGlobalUrlCache($page);
        $this->getHeaderManager()->rebuildCache($page);
        $this->rebuildSectionCache($page);
        return array_merge($globalUrlMessages);
    }

    private function rebuildSectionCache(?Page $page = null) {

    }

    private function rebuildGlobalUrlCache(?Page $page = null): array {

    }

    /**
     * @param int $globalId
     * @param Language $language
     * @return array
     */
    public function getViableParents(int $globalId, Language $language): array {
        if ($globalId === 1) return [1 => $this->getDatabase()->table(self::LOCAL_TABLE)->wherePrimary(1)->fetchField(self::LOCAL_COLUMN_TITLE)];

        $invalidGlobalIds = $this->getInvalidParents($globalId);

        $pages = $this->getDatabase()->table(self::LOCAL_TABLE)->where(
            [self::LOCAL_MAIN_COLUMN_ID . " NOT ?" => $invalidGlobalIds,
             self::LOCAL_COLUMN_LANG               => $language->getId()]
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
     * @return int[] globalIds of the parents
     */
    private function getInvalidParents(int $globalId): array {
        $invalidParents = [$globalId];

        $children = $this->getDatabase()->table(self::MAIN_TABLE)->where([self::MAIN_COLUMN_PARENT_ID => $globalId])->select(self::MAIN_COLUMN_ID)->fetchAll();
        /** @var ActiveRow $parent */
        foreach ($children as $parent) {
            $invalidParents = array_merge($this->getInvalidParents($parent[self::MAIN_COLUMN_ID]));
        }
        return $invalidParents;
    }


    /**
     * @param int $type (page|post|section)
     * @return int ID of the page added
     */
    public function addEmpty(int $type): int {
        if (!in_array($type, self::TYPES)) throw new InvalidArgumentException("Invalid type " . implode("|", self::TYPES) . " needed, $type got.");

        $globalId = $this->getDatabase()->table(self::MAIN_TABLE)->insert([
            self::MAIN_COLUMN_TYPE      => $type,
            self::MAIN_COLUMN_STATUS    => self::STATUS_DRAFT,
            self::MAIN_COLUMN_PARENT_ID => 1,
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
                self::LOCAL_COLUMN_URL     => $url,
            ])->getPrimary();
        }

        $this->onAdd($localIds);
        return $globalId;
    }

    private function getByLocalId(int $localId):?Page {
        $localRow = $this->getDatabase()->table(self::LOCAL_TABLE)->get($localId);
        if (!$localRow instanceof ActiveRow) return null;

        $language = $this->getLanguageManager()->getById($localRow[self::LOCAL_COLUMN_LANG]);
        if (!$language instanceof Language) return null; //TODO report db error

        $globalRow = $this->getDatabase()->table(self::MAIN_TABLE)->get($globalId = $localRow[self::LOCAL_MAIN_COLUMN_ID]);
        $type = Type::getById($globalRow[self::MAIN_COLUMN_TYPE]);
        if (!$type instanceof Type) return null; //TODO report db error

        return new Page(
            $globalId,
            $localId,
            $localRow[self::LOCAL_COLUMN_TITLE],
            $localRow[self::LOCAL_COLUMN_DESCRIPTION],
            $localRow[self::LOCAL_COLUMN_URL],
            $this->createPermanentUrl($language, $globalId),
            $localRow[self::LOCAL_COLUMN_IMAGE],
            $localRow[self::LOCAL_COLUMN_IMAGE_ALT],
            $type,
            $localRow[self::LOCAL_COLUMN_CONTENT],
            $this->getTagManager()->getTagsForPageId($globalId, $language),
            $this->getUserManager()->getUserIdentityById($localRow[self::LOCAL_COLUMN_AUTHOR]),
            $localRow[self::LOCAL_COLUMN_CREATED],
            $localRow[self::LOCAL_COLUMN_LAST_EDITED],
            $globalRow[self::MAIN_COLUMN_STATUS],
            $localRow[self::LOCAL_COLUMN_STATUS],
            $language,
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
     * @throws InvalidStateOfDB
     */
    private function getIfRightsAndSetNotCached(Page $page):?Page {
        if ($page->getStatus() == 1 || $this->hasRightsToSeeNonPublicPages()) {
            $page->setPageSettings($this->getSettingsManager()->getPageSettings($page->getLang()));
            $parent = ($page->getGlobalId() !== 1) ? $this->getByGlobalId($page->getLang(), $page->getParentId()) : null;
            if ($page->getGlobalId() !== 1 && is_null($parent)) throw new InvalidStateOfDB("Page that's not homepage without valid parent");
            if ($parent instanceof Page) $page->setParent($parent);
            return $page;
        }
        return null;
    }

    private function hasRightsToSeeNonPublicPages(): bool {
        return $this->getUser()->isAllowed(self::ACTION_SEE_NON_PUBLIC_PAGES);
    }

    public function hasRightsToEditGlobalPage(): bool {
        return $this->getUser()->isAllowed(self::ACTION_MANAGE);
    }

    public function hasRightsToDraft(): bool {
        return $this->getUser()->isAllowed(self::ACTION_DRAFT);
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
     * @return Page[]
     */
    public function getFiltered(int $type = null, int $visibility = null, ?Language $language, ?bool $hasTranslation, int $page, int $perPage, &$numOfPages, ?string $search) {
        $selection = $this->getDatabase()->table(self::LOCAL_TABLE)->select(self::LOCAL_TABLE . "." . self::LOCAL_MAIN_COLUMN_ID)->select(self::LOCAL_COLUMN_LANG);

        if (is_int($type)) $selection = $selection->where(self::MAIN_TABLE . "." . self::MAIN_COLUMN_TYPE, $type);

        if (is_string($search)) {
            $searchWheres = [];
            $values = [];
            $explosion = explode(" ", $search);
            foreach ($explosion as $item) {
                $searchWheres[] = "MATCH(" . self::LOCAL_SEARCH . ") AGAINST (?)";
                $values[] = $item;
            }
            if (count($explosion) === 1) {
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
            $selection->where($leastVisibility . " >= ?", self::STATUS_DELETED);

        //TODO add language filter - all translated, with no translation, by language - (select), SEARCH
        $result = $selection->page($page, $perPage, $numOfPages)->fetchAll();

        $pages = [];
        /** @var ActiveRow $item */
        foreach ($result as $item) {
            $language = $this->getLanguageManager()->getById($item[self::LOCAL_COLUMN_LANG]);
            if (!$language instanceof Language) throw new InvalidStateOfDB("page with nonexistent lang_id");
            $pages[] = $this->getByGlobalId($language, $item[self::LOCAL_MAIN_COLUMN_ID]);
        }
        return $pages;
    }


    /**
     * @param Language $lang
     * @param int $globalId
     * @return string like '/en_US/permanent/1/'
     */
    private function createPermanentUrl(Language $lang, int $globalId): string {
        $permanent = self::PAGE_URL_PERMANENT;
        return "{$lang->getCode()}/{$permanent}/{$globalId}";
    }

    public function getDefault404(): Page {
        $translator = $this->context->getByType(Translator::class);

        $pageType = Type::PAGE_TYPE;
        $lang = $this->getLanguageManager()->getByCode($translator->getLocale());
        if (!$lang instanceof Language) throw new Exception("invalid Language in translator");
        $page = new Page(
            Page::ID_404,
            Page::ID_404,
            $translator->translate("page.default.404.title"),
            $translator->translate("page.default.404.description"),
            "",
            "",
            "",
            "",
            new $pageType,
            $translator->translate("page.default.404.content"),
            [],
            null,
            null,
            null,
            1, 1,
            $lang,
            0
        );
        $page->setPageSettings($this->getSettingsManager()->getPageSettings($lang));
        return $page;

    }

    private function getUrlCacheKey(string $url, Language $language) {
        return $language->getCode() . "_" . $url;
    }

    private function getGlobalCacheKey(int $id, Language $language) {
        return $language->getCode() . "_" . $id;
    }

    private function getSettingsManager(): SettingsManager {
        if (!$this->settingsManager instanceof SettingsManager) {
            $this->settingsManager = $this->context->getByType(SettingsManager::class);
        }
        return $this->settingsManager;
    }

    private function getLanguageManager(): LanguageManager {
        if (!$this->languageManager instanceof LanguageManager) {
            $this->languageManager = $this->context->getByType(LanguageManager::class);
        }
        return $this->languageManager;
    }

    private function getDatabase(): Context {
        if (!$this->database instanceof Context) {
            $this->database = $this->context->getByType(Context::class);
        }
        return $this->database;
    }

    private function getUser(): User {
        if (!$this->user instanceof User) {
            $this->user = $this->context->getByType(User::class);
        }
        return $this->user;
    }

    private function getUserManager(): UserManager {
        if (!$this->userManager instanceof UserManager) {
            $this->userManager = $this->context->getByType(UserManager::class);
        }
        return $this->userManager;
    }

    private function getHeaderManager(): HeaderManager {
        if (!$this->headerManager instanceof HeaderManager) {
            $this->headerManager = $this->context->getByType(HeaderManager::class);
        }
        return $this->headerManager;
    }

    private function getTagManager(): TagManager {
        if (!$this->tagManager instanceof TagManager) {
            $this->tagManager = $this->context->getByType(TagManager::class);
        }
        return $this->tagManager;
    }

    /**
     * @param int $localId
     * @return Page[]
     */
    public function getSectionPosts(int $localId): array {
        return $this->sectionCache->load($localId);
    }

    private function isSection(int $globalId) {
        return boolval($this->getDatabase()->table(self::MAIN_TABLE)->where([
            self::MAIN_COLUMN_TYPE => self::TYPE_SECTION,
            self::MAIN_COLUMN_ID   => $globalId,
        ])->fetch());
    }

    private function getFromUrlCache(string $url, Language $language):?Page {
        return $this->urlCache->load($this->getUrlCacheKey($url, $language));
    }

    private function getFromGlobalCache(int $id, Language $language):?Page {
        return $this->globalCache->load($this->getGlobalCacheKey($id, $language));
    }

    /**
     * Caches every translation available
     * @param array $localIds
     */
    private function onAdd(array $localIds) {
        foreach ($localIds as $localId) {
            $this->cache($this->getByLocalId($localId));
        }
    }

    private function cache(Page $page) {
        $urlKey = $this->getUrlCacheKey($page->getUrl(), $page->getLang());
        $globalKey = $this->getGlobalCacheKey($page->getGlobalId(), $page->getLang());
        $this->urlCache->save($urlKey, $page);
        $this->globalCache->save($globalKey, $page);
    }

    private function getFreeUrl(Language $language): string {
        $unique = uniqid(self::RANDOM_URL_PREFIX, true);
        /* if exists */
        if (!$this->getDatabase()->table(self::LOCAL_TABLE)->where([self::LOCAL_COLUMN_URL => $unique, self::LOCAL_COLUMN_LANG => $language->getId()])->fetch()) return $unique;
        return $this->getFreeUrl($language);
    }
}