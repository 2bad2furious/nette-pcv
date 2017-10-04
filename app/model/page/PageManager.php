<?php
/*
 * TODO add global options like sitename, fbapp, twitter, author and stuff
 *
 */

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
        MAIN_COLUMN_STATUS = "status",
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

        CHANGES_TABLE = "page_change",
        CHANGE_COLUMN_ID = "change_id",
        CHANGES_COLUMN_PAGE_LOCAL_ID = "page_local_id",
        CHANGES_COLUMN_CREATED = "created",
        CHANGES_COLUMN_AUTHOR = "author_id",
        CHANGES_COLUMN_VERIFIED = "verified",
        CHANGES_COLUMN_VERIFICATOR = "verificator",
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
     * PageManager constructor.
     * @param IStorage $storage
     * @param Container $context
     */
    public function __construct(IStorage $storage, Container $context) {
        $cache = new Cache($storage, "page");
        $this->urlCache = $cache->derive("url");
        $this->globalCache = $cache->derive("global");
        $this->context = $context;
    }

    public function rebuildCache(int $globalId = null): array {
        $messages = [];

        $selection = $this->getDatabase()->table(self::MAIN_TABLE);
        $globalRows = $globalId === null ?
            $selection->fetchAll() :
            [$selection->where([
                self::MAIN_COLUMN_ID => $globalId,
            ])->fetch()];

        if (!$globalRows) $messages[] = (is_int($globalId)) ? [self::MESSAGE_GLOBAL_PAGE_NOT_FOUND, $globalId] : [self::MESSAGE_NO_GLOBAL_PAGES_TO_REBUILD];
        /** @var ActiveRow $globalRow */
        foreach ($globalRows as $globalRow) {
            $globalId = $globalRow[self::MAIN_COLUMN_ID];
            $localRows = $this->getDatabase()->table(self::LOCAL_TABLE)->where([
                self::LOCAL_MAIN_COLUMN_ID => $globalId,
            ])->fetchAll();

            if (!$localRows) $messages[] = [self::MESSAGE_NO_LOCAL_PAGES_FOR_GLOBAL_PAGE, $globalId];
            /** @var ActiveRow $localRow */
            foreach ($localRows as $localRow) {
                $language = $this->getLanguageManager()->getById($localRow[self::LOCAL_COLUMN_LANG]);
                if (!$language instanceof Language) {
                    //TODO do some user log or error reporting
                    continue;
                }

                $page = new Page(
                    $globalId,
                    $localRow[self::LOCAL_COLUMN_ID],
                    $localRow[self::LOCAL_COLUMN_TITLE],
                    $localRow[self::LOCAL_COLUMN_DESCRIPTION],
                    $url = $localRow[self::LOCAL_COLUMN_URL],
                    $this->createPermanentUrl($language, $globalId),
                    $localRow[self::LOCAL_COLUMN_IMAGE],
                    $localRow[self::LOCAL_COLUMN_IMAGE_ALT],
                    Type::getById($globalRow[self::MAIN_COLUMN_TYPE]),
                    $localRow[self::LOCAL_COLUMN_CONTENT],
                    $this->getTagManager()->getTagsForPageId($globalId, $language),
                    $this->getUserManager()->getUserIdentityById($localRow[self::LOCAL_COLUMN_AUTHOR]),
                    $localRow[self::LOCAL_COLUMN_CREATED],
                    $localRow[self::LOCAL_COLUMN_LAST_EDITED],
                    min($localRow[self::LOCAL_COLUMN_STATUS], $globalRow[self::MAIN_COLUMN_STATUS]),
                    $language,
                    $globalRow[self::MAIN_COLUMN_PARENT_ID]
                );

                $this->urlCache->save($this->getUrlCacheKey($url, $language), $page);
                $this->globalCache->save($this->getGlobalCacheKey($globalId, $language), $page);
            }
        }
        $this->getHeaderManager()->rebuildCache();
        return $messages;
    }


    /**
     * @param Language $language
     * @param int $id PageId
     * @return null|Page
     */
    public function getByGlobalId(Language $language, int $id): ?Page {
        $cached = $this->globalCache->load($this->getGlobalCacheKey($id, $language));
        return $cached instanceof Page ? $this->getIfRightsAndSetNotCached($cached) : null;
    }

    /**
     * @param Language $language
     * @param string $url
     * @return null|Page
     */
    public function getByUrl(Language $language, string $url):?Page {
        $cached = $this->urlCache->load($this->getUrlCacheKey($url, $language));
        return $cached instanceof Page ? $this->getIfRightsAndSetNotCached($cached) : null;
    }

    /**
     * @param Page $page
     * @return null|Page
     */
    private function getIfRightsAndSetNotCached(Page $page):?Page {
        if ($page->getStatus() == 1 || $this->hasRightsToSeeNonPublicPages()) {
            $page->setPageSettings($this->getSettingsManager()->getPageSettings($page->getLang()));
            $parent = $this->getByGlobalId($page->getLang(), $page->getParentId());
            if ($parent instanceof Page) $page->setParent($parent);
            return $page;
        }
        return null;
    }

    private function hasRightsToSeeNonPublicPages(): bool {
        return $this->getUser()->isAllowed(self::ACTION_SEE_NON_PUBLIC_PAGES);
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

    public function getDefault404(Translator $translator): Page {
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
            1,
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
}