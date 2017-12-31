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

    public function getFiltered(int $page, int $perPage, &$numOfPages, ?string $search, ?bool $codeIsGenerated) {
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
     */
    public function getAvailableLanguages($check = true): array {
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

    public function getByCode(string $langCode): ?Language {
        $cached = $this->getCodeCache()->load($langCode,
            function () use ($langCode) {
                return $this->getFromDbByCode($langCode);
            });
        return $cached instanceof Language ? $cached : null;
    }

    public function getById(int $id): ?Language {
        $cached = $this->getIdCache()->load($id,
            function () use ($id) {
                return $this->getFromDbById($id);
            });
        return $cached instanceof Language ? $cached : null;
    }

    public function cleanCache() {
        $this->getCache()->clean();
    }

    /**
     * @return Language
     * @throws InvalidState
     */
    public function getDefaultLanguage(): Language {
        $setting = $this->getSettingsManager()->get(self::SETTINGS_DEFAULT_LANGUAGE);
        if (!$setting instanceof Setting) throw new InvalidState("Default lang setting not found");
        $languageId = (int)$setting->getValue();
        $language = $this->getById($languageId);
        if (!$language instanceof Language) throw new InvalidState("Default lang $languageId not found");
        return $language;
    }

    public function createNew(): Language {
        return $this->runInTransaction(function () {
            $code = $this->getUniqueCode();

            $id = $this->getDatabase()->table(self::TABLE)->insert([
                self::COLUMN_CODE => $code,
            ])->getPrimary();

            $this->uncacheId($id);
            $this->uncacheCode($code);

            $language = $this->getById($id);

            $this->setSettings($language, 0, "", 0, 0, "", "");

            $this->trigger(self::TRIGGER_LANGUAGE_ADDED, $language);

            return $language;
        });
    }

    public function edit(Language $language, string $code, string $ga, string $title, string $separator, int $logoId, int $homePageId, int $faviconId) {
        if ($code !== $language->getCode()) {
            if (!self::isCodeGenerated($language->getCode())) throw new Exception("Cannot edit non-generated language codes");

            if (!preg_match("#" . self::COLUMN_CODE_PATTERN . "#", $code)) throw new InvalidArgumentException("Code pattern not correct");

            $this->uncache($language);

            $this->runInTransaction(function () use ($language, $code) {
                $this->getDatabase()->table(self::TABLE)
                    ->where([
                        self::COLUMN_ID => $language->getId(),
                    ])
                    ->update([
                        self::COLUMN_CODE => $code,
                    ]);
            });
        }

        $this->setSettings($language, $logoId, $ga, $faviconId, $homePageId, $separator, $title);

        $this->trigger(self::TRIGGER_LANGUAGE_EDITED, $language);
    }

    public function delete(int $id) {
        $language = $this->getById($id);
        if (!$language instanceof Language) throw new InvalidArgumentException("Language not found");

        //check if its the last
        if (count($this->getAvailableLanguages(false)) === 1) throw new CannotDeleteLastLanguage();

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

    private function getUniqueCode(): string {
        $unique = self::GENERATED_CODE_PREFIX . \Nette\Utils\Strings::truncate($uniqid = sha1(uniqid()), self::COLUMN_CODE_LENGTH - strlen(self::GENERATED_CODE_PREFIX), "");

        if ($this->getDatabase()->table(self::TABLE)->where([self::COLUMN_CODE => $unique])->fetchField(self::COLUMN_ID)) return $this->getUniqueCode();
        return $unique;
    }

    private function setSettings(Language $language, int $logoId, string $ga, int $faviconId, int $homePageId, string $titleSeparator, string $siteName) {
        $sm = $this->getSettingsManager();
        $mm = $this->getMediaManager();
        if ($logoId !== 0 &&
            (!($media = $mm->getById($logoId, MediaManager::TYPE_IMAGE)) instanceof Media)) {
            trigger_error("Logo $logoId not found, not using it.");
            $logoId = 0;
        }
        $sm->set(PageManager::SETTINGS_LOGO, $logoId, $language);

        $sm->set(PageManager::SETTINGS_GOOGLE_ANALYTICS, $ga, $language);

        if ($faviconId !== 0 && (!($media = $mm->getById($faviconId, MediaManager::TYPE_IMAGE)) instanceof Media)) {
            trigger_error("Favicon $faviconId not found, not using it.");
            $faviconId = 0;
        }
        $sm->set(PageManager::SETTINGS_FAVICON, $faviconId, $language);

        if ($homePageId !== 0 && !$this->getPageManager()->exists($homePageId)) {
            trigger_error("Page $homePageId not found, not using it.");
            $homePageId = 0;
        }
        $sm->set(PageManager::SETTINGS_HOMEPAGE, $homePageId, $language);

        $sm->set(PageManager::SETTINGS_TITLE_SEPARATOR, $titleSeparator, $language);
        $sm->set(PageManager::SETTINGS_SITE_NAME, $siteName, $language);
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