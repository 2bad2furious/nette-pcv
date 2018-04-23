<?php


use Nette\Database\IRow;

class LanguageManager extends Manager implements ILanguageManager {

    const TABLE = "language",
        COLUMN_ID = "language_id",
        COLUMN_CODE = "code", COLUMN_CODE_LENGTH = 5, COLUMN_CODE_PATTERN = "[a-z]{2}_[A-Z]{2}|[a-z]{2}",
        COLUMN_FRIENDLY = "friendly", COLUMN_FRIENDLY_LENGTH = 25,
        COLUMN_GA = "ga", COLUMN_GA_LENGTH = 15,
        COLUMN_HOMEPAGE = "homepage_id",
        COLUMN_ERRORPAGE = "errorpage_id",
        COLUMN_SITE_NAME = "site_name", COLUMN_SITE_NAME_LENGTH = 40,
        COLUMN_TITLE_SEPARATOR = "title_separator", COLUMN_TITLE_SEPARATOR_LENGTH = 20,
        COLUMN_FAVICON = "favicon_id",
        COLUMN_LOGO = "logo_id",

        SETTINGS_DEFAULT_LANGUAGE = "language.default";

    /**
     * @param int $page
     * @param int $perPage
     * @param $numOfPages
     * @param null|string $search
     * @return array
     * @throws LanguageByIdNotFound
     */
    public function getFiltered(int $page, int $perPage, &$numOfPages, ?string $search): array {
        $selection = $this->getDatabase()
            ->table(self::TABLE)
            ->order(self::COLUMN_ID);

        if (is_string($search)) {
            $selection->where(self::COLUMN_CODE . " LIKE (?)", "%" . $search . "%");
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
     * @return Language[]
     * TODO cache?
     * @throws LanguageByIdNotFound
     */
    public function getAvailableLanguages(): array {
        $data = $this->getDatabase()
            ->table(self::TABLE)
            ->order(self::COLUMN_ID);

        $langs = [];
        /** @var IRow $lang */
        while ($lang = $data->fetch()) {
            $langs[$lang[self::COLUMN_ID]] = $this->getById($lang[self::COLUMN_ID]);
        }
        return $langs;
    }

    /**
     * @param string $langCode
     * @param bool $throw
     * @return Language|null
     * @throws LanguageByCodeNotFound
     */
    public function getByCode(string $langCode, bool $throw = true): ?Language {
        $cached = $this->getCodeCache()->load($langCode,
            function () use ($langCode) {
                return $this->getFromDbByCode($langCode);
            });
        if (!$cached && $throw) throw new LanguageByCodeNotFound($langCode);

        return $cached instanceof Language ? $cached : null;
    }

    /**
     * @param int $id
     * @param bool $throw
     * @return Language|null
     * @throws LanguageByIdNotFound
     */
    public function getById(int $id, bool $throw = true): ?Language {
        $cached = $this->getIdCache()->load($id,
            function () use ($id) {
                return $this->getFromDbById($id);
            });
        if (!$cached && $throw) throw new LanguageByIdNotFound($id);

        return $cached instanceof Language ? $cached : null;
    }

    public function cleanCache() {
        $this->getIdCache()->clean();
        $this->getCodeCache()->clean();
    }

    /**
     * @return Language
     * @throws LanguageByIdNotFound
     */
    public function getDefaultLanguage(): Language {
        $setting = $this->getSettingsManager()->get(self::SETTINGS_DEFAULT_LANGUAGE);
        $languageId = (int)$setting->getValue();
        return $this->getById($languageId);
    }

    /**
     * @param int $languageId
     * @param string $friendly
     * @param string $ga
     * @param string $title
     * @param string $separator
     * @param int|null $logoId
     * @param int|null $homePageId
     * @param int|null $faviconId
     * @param int|null $error404page
     * @throws FileNotFoundById
     * @throws LanguageByIdNotFound
     * @throws Throwable
     */
    public function edit(int $languageId, string $friendly, string $ga, string $title, string $separator, ?int $logoId, ?int $homePageId, ?int $faviconId, ?int $error404page) {
        $language = $this->getById($languageId);

        if (!is_null($logoId))
            $this->getMediaManager()->getById($logoId, FileManager::TYPE_IMAGE);

        if (!is_null($error404page))
            $this->getPageManager()->exists($error404page,$language->getId());

        if (!is_null($faviconId))
            $this->getMediaManager()->getById($faviconId, FileManager::TYPE_IMAGE);

        if (!is_null($homePageId))
            $this->getPageManager()->exists($homePageId,$language->getId());

        if (mb_strlen($ga) > self::COLUMN_GA_LENGTH)
            throw new InvalidArgumentException("Google Analytics code must be at most " . self::COLUMN_GA_LENGTH . " long");

        if (mb_strlen($friendly) > self::COLUMN_FRIENDLY_LENGTH)
            throw new InvalidArgumentException("Friendly name must be at most " . self::COLUMN_FRIENDLY_LENGTH . " long");

        if (mb_strlen($title) > self::COLUMN_SITE_NAME_LENGTH)
            throw new InvalidArgumentException("Site name must be at most " . self::COLUMN_SITE_NAME_LENGTH . " long");

        if (mb_strlen($separator) > self::COLUMN_TITLE_SEPARATOR_LENGTH)
            throw new InvalidArgumentException("Separator must be at most " . self::COLUMN_TITLE_SEPARATOR_LENGTH . " long");

        $this->uncache($language);

        $this->runInTransaction(function () use ($language, $friendly, $ga, $title, $separator, $logoId, $homePageId, $faviconId, $error404page) {
            return $this->getDatabase()->table(self::TABLE)
                ->wherePrimary($language->getId())
                ->update([
                    self::COLUMN_FRIENDLY        => $friendly,
                    self::COLUMN_TITLE_SEPARATOR => $separator,
                    self::COLUMN_GA              => $ga,
                    self::COLUMN_SITE_NAME       => $title,
                    self::COLUMN_LOGO            => $logoId,
                    self::COLUMN_HOMEPAGE        => $homePageId,
                    self::COLUMN_FAVICON         => $faviconId,
                    self::COLUMN_ERRORPAGE       => $error404page,
                ]);
        });

        $this->trigger(self::TRIGGER_LANGUAGE_EDITED, $language);
    }

    /**
     * @param string $code
     * @param string $title
     * @param string $friendly
     * @return Language
     * @throws LanguageByCodeNotFound
     * @throws LanguageByIdNotFound
     * @throws InvalidArgumentException
     * @throws Throwable
     */
    public function add(string $code, string $title, string $friendly): Language {
        if (!preg_match("#^" . self::COLUMN_CODE_PATTERN . "$#", $code))
            throw new InvalidArgumentException("Code pattern not correct");

        if (mb_strlen($title) > self::COLUMN_SITE_NAME_LENGTH)
            throw new InvalidArgumentException("SITE NAME must be at most " . self::COLUMN_SITE_NAME_LENGTH . " long");

        if (mb_strlen($friendly) > self::COLUMN_FRIENDLY_LENGTH)
            throw new InvalidArgumentException("Frienldy must be at most " . self::COLUMN_FRIENDLY_LENGTH . " long");

        if ($this->getByCode($code, false) instanceof Language)
            throw new InvalidArgumentException("Code already used");

        $this->uncacheCode($code);//uncached new code


        $faviconId = (int)$this->getSettingsManager()->get(PageManager::SETTINGS_FAVICON)->getValue();

        $logoId = (int)$this->getSettingsManager()->get(PageManager::SETTINGS_LOGO)->getValue();

        $ga = $this->getSettingsManager()->get(PageManager::SETTINGS_GOOGLE_ANALYTICS)->getValue();

        $separator = $this->getSettingsManager()->get(PageManager::SETTINGS_TITLE_SEPARATOR)->getValue();

        $id = $this->runInTransaction(function () use ($code, $title, $friendly, $faviconId, $logoId, $ga, $separator) {
            $id = $this->getDatabase()->table(self::TABLE)->insert([
                self::COLUMN_CODE            => $code,
                self::COLUMN_FRIENDLY        => $friendly,
                self::COLUMN_SITE_NAME       => $title,
                self::COLUMN_ERRORPAGE       => 0,
                self::COLUMN_HOMEPAGE        => 0,
                self::COLUMN_FAVICON         => $faviconId,
                self::COLUMN_LOGO            => $logoId,
                self::COLUMN_GA              => $ga,
                self::COLUMN_TITLE_SEPARATOR => $separator,
            ])->getPrimary();

            $this->uncacheId($id);

            return $id;
        });

        $language = $this->getById($id);

        $this->trigger(self::TRIGGER_LANGUAGE_ADDED, $language);

        return $language;
    }

    /**
     * @param int $id
     * @throws CannotDeleteLastLanguage
     * @throws LanguageByIdNotFound
     * @throws Throwable
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
            $row[self::COLUMN_CODE],
            $row[self::COLUMN_FRIENDLY],
            $row[self::COLUMN_SITE_NAME],
            $row[self::COLUMN_TITLE_SEPARATOR],
            $row[self::COLUMN_GA],
            $row[self::COLUMN_LOGO],
            $row[self::COLUMN_FAVICON],
            $row[self::COLUMN_HOMEPAGE],
            $row[self::COLUMN_ERRORPAGE]
        );
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