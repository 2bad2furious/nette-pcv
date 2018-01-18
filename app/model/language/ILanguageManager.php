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

    public function getFiltered(int $page, int $perPage, &$numOfPages, ?string $search, ?bool $codeIsGenerated): array;

    /**
     * @return Language[]
     */
    public function getAvailableLanguages(): array;

    /**
     * @param string $langCode of the language
     * @param bool $throw if not found
     * @return Language|null
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
     */
    public function getDefaultLanguage(): Language;

    public function add(string $code, string $title): Language;

    public function edit(int $langId, string $ga, string $title, string $separator, int $logoId, int $homePageId, int $faviconId, int $error404page);

    /**
     * @param int $id
     * @throws CannotDeleteLastLanguage
     */
    public function delete(int $id);

}