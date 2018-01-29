<?php

interface IPageManager extends IManager {
    const TYPE_PAGE = 1, TYPE_POST = 0, TYPE_ALL = null;//all is just for Filtered
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
        SETTINGS_HOMEPAGE = "site.homepage_id",
        SETTING_404 = "site.404_id";

    const ACTION_SEE_NON_PUBLIC_PAGES = "page.non_public";

    public function exists(int $globalId, ?int $languageId = null, bool $throw = false): bool;

    /**
     * @param int $langId
     * @param int|null $type
     * @return array
     */
    public function getAllPages(int $langId, ?int $type = self::TYPE_ALL): array;

    /**
     * @param int $globalId
     * @param int $langId
     * @return PageWrapper[]
     */
    public function getViableParents(int $globalId, int $langId): array;

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
    public function getFiltered(?int $type = null, ?int $visibility = null, ?Language $language, ?bool $hasTranslation, int $page, int $perPage, &$numOfPages, ?string $search);

    public function cleanCache();

    /**
     * @param int $languageId
     * @param int $id PageId
     * @param bool $throw
     * @return null|PageWrapper
     */
    public function getByGlobalId(int $languageId, int $id, bool $throw = true): ?PageWrapper;

    /**
     * @param int $languageId
     * @param string $url
     * @return null|PageWrapper
     */
    public function getByUrl(int $languageId, string $url): ?PageWrapper;

    public function get404(int $languageId): ?PageWrapper;

    /**
     * @param string $url
     * @param int $languageId
     * @param int|null $localId
     * @return bool
     */
    public function isUrlAvailable(string $url, int $languageId, ?int $localId): bool;

    /**
     * @param int $type (page|post)
     * @return int ID of the page added
     * @throws Exception
     */
    public function addEmpty(int $type): int;

    public function update(int $pageId, int $langId, int $parentId, string $title, string $description, string $url, int $globalVisibility, int $localVisibility, string $content, int $imageId);

    public function delete(int $globalId);

    public function isDefaultUrl(string $url): bool;

    public function getDefaultTitle():string;
}