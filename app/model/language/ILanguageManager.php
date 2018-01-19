<?php

/**
 * Interface ILanguageManager
 *
 * Language has 3 phases
 * 1. Created - creates localization for this language
 * 2. Pages set (404 and home)
 */
interface ILanguageManager extends IManager {

    const TRIGGER_LANGUAGE_DELETED = "trigger.language.deleted";
    const TRIGGER_LANGUAGE_ADDED = "trigger.language.added";
    const TRIGGER_LANGUAGE_EDITED = "trigger.language.edited";

    /**
     * @param int $page
     * @param int $perPage
     * @param $numOfPages
     * @param null|string $search
     * @return array
     * @throws LanguageByIdNotFound
     */
    public function getFiltered(int $page, int $perPage, &$numOfPages, ?string $search): array;

    /**
     * @return Language[]
     * TODO cache?
     * @throws LanguageByIdNotFound
     */
    public function getAvailableLanguages(): array;

    /**
     * @param string $langCode
     * @param bool $throw
     * @return Language|null
     * @throws LanguageByCodeNotFound
     */
    public function getByCode(string $langCode, bool $throw = true): ?Language;

    /**
     * @param int $id
     * @param bool $throw
     * @return Language|null
     * @throws LanguageByIdNotFound
     */
    public function getById(int $id, bool $throw = true): ?Language;


    public function cleanCache();

    /**
     * @return Language
     * @throws LanguageByIdNotFound
     */
    public function getDefaultLanguage(): Language;


    public function add(string $code, string $title,string $friendly): Language;

    /**
     * @param int $languageId
     * @param string $friendly
     * @param string $ga
     * @param string $title
     * @param string $separator
     * @param int $logoId
     * @param int $homePageId
     * @param int $faviconId
     * @param int $error404page
     * @throws LanguageByIdNotFound
     * @throws Throwable
     */
    public function edit(int $languageId,string $friendly, string $ga, string $title, string $separator, int $logoId, int $homePageId, int $faviconId, int $error404page);

    /**
     * @param int $id
     * @throws CannotDeleteLastLanguage
     */
    public function delete(int $id);

}