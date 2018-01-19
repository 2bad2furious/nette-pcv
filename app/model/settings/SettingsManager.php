<?php


use Nette\Database\Table\IRow;

class SettingsManager extends Manager implements ISettingsManager {
    const
        TABLE = "settings",
        COLUMN_ID = "settings_id",
        COLUMN_OPTION = "option", COLUMN_OPTION_LENGTH = 60,
        COLUMN_VALUE = "value",
        COLUMN_LANG = "lang_id";

    /**
     * @param string $option
     * @param int|null $language
     * @param bool $throw
     * @return null|SettingWrapper
     * @throws SettingNotFound
     */
    public function get(string $option, ?int $language = null, bool $throw = true): ?SettingWrapper {
        $cached = $this->getCache()->load($this->getCacheKey($option, $language),
            function () use ($option, $language) {
                return $this->getFromDb($option, (int)$language);
            });

        if ($cached instanceof Setting) {
            return new SettingWrapper($cached, $this->getLanguageManager());
        } else if ($throw) throw new SettingNotFound($option, (int)$language);

        return null;
    }

    /**
     * @param string $option
     * @param string $value
     * @param int|null $languageId
     * @return SettingWrapper
     * @throws SettingNotFound
     * @throws Throwable
     */
    public function set(string $option, string $value, ?int $languageId = null): SettingWrapper {
        $existing = $this->get($option, $languageId, false);

        $this->uncache($cacheKey = $this->getCacheKey($option, $languageId));

        $this->runInTransaction(function () use ($languageId, $option, $value, $existing) {


            $whereData = [
                self::COLUMN_OPTION => $option,
                self::COLUMN_LANG   => $languageId,
            ];

            $updateData = [
                self::COLUMN_VALUE => $value,
            ];

            if ($existing instanceof SettingWrapper) {
                $this->getDatabase()->table(self::TABLE)
                    ->where($whereData)
                    ->update($updateData);

            } else {
                $insertData = array_merge($updateData, $whereData);
                $this->getDatabase()->table(self::TABLE)
                    ->insert($insertData);
            }

        });

        return $this->get($option, $languageId);
    }

    public function cleanCache() {
        $this->getCache()->clean();
    }

    /**
     * @param string $option
     * @param int $langId
     * @return false|Setting
     */
    private function getFromDb(string $option, int $langId) {
        $data = $this->getDatabase()
            ->table(self::TABLE)
            ->where([
                self::COLUMN_OPTION => $option,
                self::COLUMN_LANG   => $langId,
            ])->fetch();

        return $data instanceof IRow ? $this->getFromRow($data) : false;
    }

    private function getFromRow(IRow $row): Setting {
        return new Setting($row[self::COLUMN_ID], $row[self::COLUMN_LANG], $row[self::COLUMN_OPTION], $row[self::COLUMN_VALUE]);
    }

    private function getCache(): Cache {
        static $cache = null;
        return $cache instanceof Cache ? $cache : $cache = new Cache($this->getDefaultStorage(), "settings");
    }

    private function uncache(string $key) {
        $this->getCache()->remove($key);
    }

    private function getCacheKey(string $option, ?int $language): string {
        return $option . "_" . (int)$language;
    }
}