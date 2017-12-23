<?php

use Nette\Database\Table\ActiveRow;

class LanguageManagerOld extends Manager implements ILanguageManager {

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
            $selection = $selection->where([self::COLUMN_CODE . ($codeIsGenerated ? "" : "NOT LIKE (?)") => self::GENERATED_CODE_PREFIX . "%"]);
        }

        if (is_string($search)) {
            $selection = $selection->where(self::COLUMN_CODE. " LIKE (?)", "%" . $search . "%");
        }

        $data = $selection->page($page, $perPage, $numOfPages)->fetchAll();
        $languages = [];

        foreach ($data as $row) {
            $langId = $row[self::COLUMN_ID];
            $languages[] = $this->getFromCacheById($langId);
        }
        return $languages;
    }

    /**
     * @param bool $asObjects
     * @param bool $check whether to include languages that are not finished (=code is generated)
     * @return array
     */
    public function getAvailableLanguages($asObjects = false, $check = true): array {
        $data = $this->getDatabase()
            ->table(self::TABLE)
            ->order(self::COLUMN_ID);

        if ($check)
            $data = $data->where([self::COLUMN_CODE . " NOT LIKE(?)" => self::GENERATED_CODE_PREFIX . "%"]);

        $data = $data->fetchAll();
        $langs = [];
        /** @var ActiveRow $lang */
        foreach ($data as $lang) {
            $langs[$lang[self::COLUMN_ID]] = ($asObjects) ? $this->createFromRow($lang) : $lang[self::COLUMN_CODE];
        }
        return $langs;
    }

    /**
     * @return Language
     * @throws Exception
     */
    public function getDefaultLanguage(): Language {
        $defaultLang = $this->getSettingsManager()->get(self::SETTINGS_DEFAULT_LANGUAGE);

        if (!($language = $defaultLang) instanceof Setting || !($language = $this->getById((int)$defaultLang->getValue())) instanceof Language) {
            dump($defaultLang, $this->getById((int)$defaultLang->getValue()));
            throw new Exception("DefaultLang not set or doesnt exist");
        }

        return $language;
    }

    public function getByCode(string $langCode): ?Language {
        return $this->getCodeCache()->load($langCode);
    }

    private function createFromRow(ActiveRow $data): Language {
        return new Language(
            $data[self::COLUMN_ID],
            $data[self::COLUMN_CODE]
        );
    }

    public function getById(int $id):?Language {
        return $this->getFromCacheById($id);
    }

    protected function getBy(array $where):?Language {
        $data = $this->getDatabase()->table(self::TABLE)->where($where)->fetch();

        if ($data instanceof Nette\Database\Table\ActiveRow) {
            return $this->createFromRow($data);
        }
        return null;
    }

    public function rebuildCache() {
        $this->throwIfNoRights(self::ACTION_CACHE);
        $this->getIdCache()->clean();
        $this->getCodeCache()->clean();
        foreach ($this->getAvailableLanguages(true, false) as $language) {
            $this->cache($language);
        }
    }

    public function createNew(): Language {
        $this->throwIfNoRights(self::ACTION_MANAGE);
        $code = $this->getUniqueCode();
        $id = $this->getDatabase()->table(self::TABLE)->insert([
            self::COLUMN_CODE => $code,
        ])->getPrimary();

        $language = $this->getFromDbById($id);
        $this->cache($language);

        $sm = $this->getSettingsManager();
        $sm->set(PageManager::SETTINGS_LOGO, 0, $language);
        $sm->set(PageManager::SETTINGS_GOOGLE_ANALYTICS, "", $language);
        $sm->set(PageManager::SETTINGS_FAVICON, 0, $language);
        $sm->set(PageManager::SETTINGS_HOMEPAGE, 0, $language);
        $sm->set(PageManager::SETTINGS_TITLE_SEPARATOR, "", $language);
        $sm->set(PageManager::SETTINGS_SITE_NAME, "", $language);

        $this->trigger(self::TRIGGER_LANGUAGE_ADDED, $language);
        return $language;
    }

    /**
     * @param int $id
     * @throws CannotDeleteLastLanguage
     */
    public function delete(int $id) {
        $this->throwIfNoRights(self::ACTION_MANAGE);
        $language = $this->getById($id);
        if (!$language instanceof Language) throw new InvalidArgumentException("Language does not exist");

        //check if its the last
        if (count($this->getAvailableLanguages(false, false)) === 1) throw new CannotDeleteLastLanguage();

        $this->getIdCache()->remove($language->getId());
        $this->getCodeCache()->remove($language->getCode());

        $this->getDatabase()->table(self::TABLE)
            ->where(self::COLUMN_ID, $language->getId())
            ->delete();

        $this->trigger(self::TRIGGER_LANGUAGE_DELETED, $language);
    }

    private function getUniqueCode(): string {
        $unique = self::GENERATED_CODE_PREFIX . \Nette\Utils\Strings::truncate($uniqid = sha1(uniqid()), self::COLUMN_CODE_LENGTH - strlen(self::GENERATED_CODE_PREFIX), "");
        if ($this->getDatabase()->table(self::TABLE)->where([self::COLUMN_CODE => $unique])->fetchField(self::COLUMN_ID)) return $this->getUniqueCode();
        return $unique;
    }

    public function edit(Language $language, string $code, string $ga, string $title, string $separator, int $logoId, int $homePageId, int $faviconId) {
        if ($code !== $language->getCode()) {
            if (!self::isCodeGenerated($language->getCode())) throw new Exception("Cannot edit non-generated language codes");

            if (!preg_match("#" . self::COLUMN_CODE_PATTERN . "#", $code)) throw new InvalidArgumentException("Code pattern not correct");

            $this->getDatabase()->table(self::TABLE)
                ->where([
                    self::COLUMN_ID => $language->getId(),
                ])
                ->update([
                    self::COLUMN_CODE => $code,
                ]);
            $this->cache($this->getFromDbById($language->getId()));
        }

        $sm = $this->getSettingsManager();
        $mm = $this->getMediaManager();
        if ($logoId !== 0 &&
            (!($media = $mm->getById($logoId)) instanceof Media || !$media->isImage()))
            throw new InvalidArgumentException("logoId does not corespond to an image or nothing at all");
        $sm->set(PageManager::SETTINGS_LOGO, $logoId, $language);
        $sm->set(PageManager::SETTINGS_GOOGLE_ANALYTICS, $ga, $language);

        if ($faviconId !== 0 && (!($media = $mm->getById($faviconId)) instanceof Media || !$media->isImage()))
            throw new InvalidArgumentException("logoId does not corespond to an image or nothing at all");
        $sm->set(PageManager::SETTINGS_FAVICON, $faviconId, $language);
        if (!$this->getPageManager()->exists($homePageId)) throw new InvalidArgumentException("HomePage does not exist");
        $sm->set(PageManager::SETTINGS_HOMEPAGE, $homePageId, $language);
        $sm->set(PageManager::SETTINGS_TITLE_SEPARATOR, $separator, $language);
        $sm->set(PageManager::SETTINGS_SITE_NAME, $title, $language);
        $this->trigger(self::TRIGGER_LANGUAGE_EDITED, $language);
    }

    private function cache(Language $language) {
        $this->getIdCache()->save($language->getId(), $language);
        $this->getCodeCache()->save($language->getCode(), $language);
    }

    private function getFromDbById($id): Language {
        $language = $this->getDatabase()->table(self::TABLE)->where(self::COLUMN_ID, $id)->fetch();
        return $this->createFromRow($language);
    }

    public static function isCodeGenerated(string $code): bool {
        return substr($code, 0, strlen(LanguageManagerOld::GENERATED_CODE_PREFIX)) === LanguageManagerOld::GENERATED_CODE_PREFIX;
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

    private function getFromCacheById(int $langId):?Language {
        return new Language($langId,"en_US");
    }
}