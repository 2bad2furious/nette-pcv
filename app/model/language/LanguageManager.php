<?php


use Nette\Database\IRow;

class LanguageManager extends Manager implements ILanguageManager {

    const TABLE = "language",
        COLUMN_ID = "language_id",
        COLUMN_CODE = "code", COLUMN_CODE_LENGTH = 5, COLUMN_CODE_PATTERN = "[a-z]{2}_[A-Z]{2}|[a-z]{2}",

        SETTINGS_DEFAULT_LANGUAGE = "language.default";
    const GENERATED_CODE_PREFIX = "glc";
    const ACTION_CACHE = "language.cache",
        ACTION_MANAGE = "language.manage";

    public function getFiltered(int $page, int $perPage, &$numOfPages, ?string $search, ?bool $codeIsGenerated): array {
        $selection = $this->getDatabase()
            ->table(self::TABLE)
            ->order(self::COLUMN_ID);

        if (is_bool($codeIsGenerated)) {
            $selection = $selection->where([self::COLUMN_CODE . ($codeIsGenerated ? " LIKE (?)" : " NOT LIKE (?)") => self::GENERATED_CODE_PREFIX . "%"]);
        }

        if (is_string($search)) {
            $selection = $selection->where(self::COLUMN_CODE . " LIKE (?)", "%" . $search . "%");
        }

        $data = $selection->page($page, $perPage, $numOfPages);
        if ($numOfPages === 0) $numOfPages = 1;
        $languages = [];

        while ($row = $data->fetch()) {
            $langId = $row[self::COLUMN_ID];
            $languages[] = $this->getById($langId);
        }
        return $languages;
    }

    /**
     * @param bool $check whether to include languages that are not finished (=code is generated)
     * @return Language[]
     * TODO cache?
     */
    public function getAvailableLanguages(bool $check = true): array {
        $data = $this->getDatabase()
            ->table(self::TABLE)
            ->order(self::COLUMN_ID);

        if ($check)
            $data = $data->where([self::COLUMN_CODE . " NOT LIKE(?)" => self::GENERATED_CODE_PREFIX . "%"]);

        $langs = [];
        /** @var IRow $lang */
        while ($lang = $data->fetch()) {
            $langs[$lang[self::COLUMN_ID]] = $this->getById($lang[self::COLUMN_ID]);
        }
        return $langs;
    }

    public function getByCode(string $langCode, bool $throw = true): ?Language {
        $cached = $this->getCodeCache()->load($langCode,
            function () use ($langCode) {
                return $this->getFromDbByCode($langCode);
            });
        if (!$cached && $throw) throw new LanguageByCodeNotFound($langCode);

        return $cached instanceof Language ? $cached : null;
    }

    public function getById(int $id, bool $throw = true): ?Language {
        $cached = $this->getIdCache()->load($id,
            function () use ($id) {
                return $this->getFromDbById($id);
            });
        if (!$cached && $throw) throw new LanguageByIdNotFound($id);

        return $cached instanceof Language ? $cached : null;
    }

    public function cleanCache() {
        $this->getCache()->clean();
    }

    /**
     * @return Language
     * @throws LanguageByIdNotFound
     */
    public function getDefaultLanguage(): Language {
        $setting = $this->getSettingsManager()->get(self::SETTINGS_DEFAULT_LANGUAGE, null);
        $languageId = (int)$setting->getValue();
        return $this->getById($languageId);
    }

    public function edit(int $languageId, string $ga, string $title, string $separator, int $logoId, int $homePageId, int $faviconId, int $error404page) {
        $language = $this->getById($languageId);

        $this->setSettings($languageId, $logoId, $ga, $faviconId, $homePageId, $separator, $title, $error404page);

        $this->trigger(self::TRIGGER_LANGUAGE_EDITED, $language);
    }

    public function add(string $code, string $title): Language {
        if (!preg_match("#" . self::COLUMN_CODE_PATTERN . "#", $code))
            throw new InvalidArgumentException("Code pattern not correct");

        if ($this->getByCode($code, false) instanceof Language)
            throw new InvalidArgumentException("Code already used");

        $this->uncacheCode($code);//uncached new code

        $id = $this->runInTransaction(function () use ($code) {
            $id = $this->getDatabase()->table(self::TABLE)->insert([
                self::COLUMN_CODE => $code,
            ])->getPrimary();

            $this->uncacheId($id);

            return $id;
        });

        $this->setSettings($id, 0, "", 0, 0, " | ", $title, 0);

        $language = $this->getById($id);

        $this->trigger(self::TRIGGER_LANGUAGE_ADDED, $language);

        return $language;
    }

    /**
     * @param int $id
     * @throws CannotDeleteLastLanguage
     */
    public function delete(int $id) {
        $language = $this->getById($id);

        //check if its the last
        if (count($this->getAvailableLanguages()) === 1) throw new CannotDeleteLastLanguage();

        $this->uncache($language);

        $this->runInTransaction(function () use ($language) {
            $this->getDatabase()->table(self::TABLE)
                ->where(self::COLUMN_ID, $language->getId())
                ->delete();
        });

        $this->trigger(self::TRIGGER_LANGUAGE_DELETED, $language);
    }

    public static function isCodeGenerated(string $code): bool {
        return substr($code, 0, strlen(self::GENERATED_CODE_PREFIX)) === self::GENERATED_CODE_PREFIX;
    }

    private function getCodeCache(): Cache {
        static $cache = null;
        return $cache instanceof Cache ? $cache : $cache = $this->getCache()->derive("code");
    }

    private function getIdCache(): Cache {
        static $cache = null;
        return $cache instanceof Cache ? $cache : $cache = $this->getCache()->derive("id");
    }

    private function getCache(): Cache {
        static $cache = null;
        return $cache instanceof Cache ? $cache : $cache = new Cache($this->getDefaultStorage(), "language");
    }

    /**
     * @param string $langCode
     * @return false|Language
     */
    private function getFromDbByCode(string $langCode) {
        return $this->getFromDb([self::COLUMN_CODE => $langCode]);
    }

    /**
     * @param int $id
     * @return false|Language
     */
    private function getFromDbById(int $id) {
        return $this->getFromDb([self::COLUMN_ID => $id]);
    }

    /**
     * @param array $where
     * @return false|Language
     */
    private function getFromDb(array $where) {
        $data = $this->getDatabase()->table(self::TABLE)
            ->where($where)
            ->fetch();

        return $data instanceof IRow ? $this->createFromRow($data) : false;
    }

    private function createFromRow(IRow $row): Language {
        return new Language(
            $row[self::COLUMN_ID],
            $row[self::COLUMN_CODE]
        );
    }

    private function setSettings(int $languageId, int $logoId, string $ga, int $faviconId, int $homePageId, string $titleSeparator, string $siteName, int $error404pageId) {
        $sm = $this->getSettingsManager();

        $sm->set(PageManager::SETTINGS_LOGO, $logoId, $languageId);

        $sm->set(PageManager::SETTINGS_GOOGLE_ANALYTICS, $ga, $languageId);

        $sm->set(PageManager::SETTINGS_FAVICON, $faviconId, $languageId);

        $sm->set(PageManager::SETTINGS_HOMEPAGE, $homePageId, $languageId);

        $sm->set(PageManager::SETTINGS_TITLE_SEPARATOR, $titleSeparator, $languageId);

        $sm->set(PageManager::SETTINGS_SITE_NAME, $siteName, $languageId);

        $sm->set(PageManager::SETTING_404, $error404pageId, $languageId);
    }

    private function uncache(Language $language) {
        $this->uncacheId($language->getId());
        $this->uncacheCode($language->getCode());
    }

    private function uncacheId(int $id) {
        $this->getIdCache()->remove($id);
    }

    private function uncacheCode(string $code) {
        $this->getCodeCache()->remove($code);
    }

}