<?php

interface ILanguageManager extends IManager {

    const TRIGGER_LANGUAGE_DELETED = "trigger.language.deleted";
    const TRIGGER_LANGUAGE_ADDED = "trigger.language.added";
    const TRIGGER_LANGUAGE_EDITED = "trigger.language.edited";

    public function getFiltered(int $page, int $perPage, &$numOfPages, ? $search, ? $codeIsGenerated);

    /**
     * @param bool $asObjects
     * @param bool $check whether to include languages that are not finished (=code is generated)
     * @return array
     */
    public function getAvailableLanguages($asObjects = false, $check = true): array;

    public function getByCode(string $langCode): ?Language;

    public function getById(int $id): ?Language;

    public function cleanCache();

    /**
     * @return Language
     * @throws InvalidState
     */
    public function getDefaultLanguage(): Language;

    public function createNew(): Language;

    public function edit(Language $language, string $code, string $ga, string $title, string $separator, int $logoId, int $homePageId, int $faviconId);

    /**
     * @param int $id
     * @throws CannotDeleteLastLanguage
     */
    public function delete(int $id);
}