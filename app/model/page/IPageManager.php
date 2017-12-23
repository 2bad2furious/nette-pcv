<?php

interface IPageManager extends IManager {
    const DEFAULT_TITLE = "admin.global.page.no_title";

    const TRIGGER_PAGE_ADDED = "trigger.page.added",
        TRIGGER_PAGE_EDITED = "trigger.page.edited",
        TRIGGER_PAGE_DELETED = "trigger.page.deleted";

    const PAGE_URL_PERMANENT = "permanent",
        PAGE_URL_BLACKLIST = [self::PAGE_URL_PERMANENT],

        SETTINGS_FAVICON = "site.favicon",
        SETTINGS_SITE_NAME = "site.title",
        SETTINGS_GOOGLE_ANALYTICS = "site.ga",
        SETTINGS_TITLE_SEPARATOR = "site.title_separator",
        SETTINGS_LOGO = "site.logo",
        SETTINGS_HOMEPAGE = "site.homepage_id";

    const ACTION_SEE_NON_PUBLIC_PAGES = "page.non_public";

    public function exists(int $globalId): bool;

    /**
     * @param Language $language
     * @return Page[]
     */
    public function getAllPages(Language $language): array;

    /**
     * @param int $globalId
     * @param Language $language
     * @return Page[]
     * @throws InvalidArgumentException if invalid type
     */
    public function getViableParents(int $globalId, Language $language): array;

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
    public function getFiltered(?int $type = null, ?int $visibility = null, ?Language $language, ?bool $hasTranslation, int $page, int $perPage, &$numOfPages, ?string $search);

    public function cleanCache();

    public function getHomePage(Language $language): ?Page;

    /**
     * @param Language $language
     * @param int $id PageId
     * @return null|Page
     */
    public function getByGlobalId(Language $language, int $id): ?Page;

    /**
     * @param Language $language
     * @param string $url
     * @return null|Page
     */
    public function getByUrl(Language $language, string $url): ?Page;

    public function getDefault404(): Page;

    /**
     * @param string $url
     * @param Language $language
     * @param int|null $localId
     * @return bool
     */
    public function isUrlAvailable(string $url, Language $language, ?int $localId): bool;

    /**
     * @param int $type (page|post)
     * @return int ID of the page added
     * @throws Exception
     */
    public function addEmpty(int $type): int;

    public function update(Page $page, int $parentId, string $title, string $description, string $url, int $globalVisibility, int $localVisibility, string $content, int $imageId);

    public function delete(int $globalId);

    public static function isDefaultUrl(string $url): bool;
}