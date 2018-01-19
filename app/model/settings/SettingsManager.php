<?php


use Nette\Database\Table\IRow;

class SettingsManager extends Manager implements ISettingsManager {
    const
        TABLE = "settings",
        COLUMN_ID = "settings_id",
        COLUMN_OPTION = "option", COLUMN_OPTION_LENGTH = 60,
        COLUMN_VALUE = "value",
        COLUMN_LANG = "lang_id";

    public function get(string $option, ?int $language = null, bool $throw = true):?SettingWrapper {
        $cached = $this->getCache()->load($this->getCacheKey($option, $language),
            function () use ($option, $language) {
                return $this->getFromDb($option, (int)$language);
            });

        if ($cached instanceof Setting) {
            return new SettingWrapper($cached, $this->getLanguageManager());
        } else if ($throw) throw new SettingNotFound($option, $language instanceof Language ? $language->getId() : 0);

        return null;
    }

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

    public function getPageSettings(int $langId): PageSettings {
        return new PageSettings(
            $this->getLocalOrGlobal(PageManager::SETTINGS_SITE_NAME, $langId)->getValue(),
            $this->getLocalOrGlobal(PageManager::SETTINGS_GOOGLE_ANALYTICS, $langId)->getValue(),
            $this->getLocalOrGlobal(PageManager::SETTINGS_TITLE_SEPARATOR, $langId)->getValue(),
            $this->getLogo($langId),
            $this->getFavicon($langId)
        );
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

    private function getLocalOrGlobal(string $option, int $languageId): SettingWrapper {
        $local = $this->get($option, $languageId);
        return ($local->getValue()) ? $local : $this->get($option, null);
    }

    //TODO fix copy-paste
    private function getFavicon(int $languageId):?Media {
        $option = PageManager::SETTINGS_FAVICON;
        $faviconSetting = $this->getLocalOrGlobal($option, $languageId);
        $faviconId = (int)$faviconSetting->getValue();
        $favicon = $this->getMediaManager()->getById($faviconId, MediaManager::TYPE_IMAGE);
        if (!$favicon instanceof Media && $faviconId !== 0) {
            trigger_error("Favicon not found, unsetting.");
            $this->set($option, 0, $faviconSetting->getLanguageId());

            return $this->getFavicon($languageId);
        }
        return $favicon;
    }

    private function getLogo(int $languageId):?Media {
        $option = PageManager::SETTINGS_LOGO;
        $logoSetting = $this->getLocalOrGlobal($option, $languageId);
        $logoId = (int)$logoSetting->getValue();
        $logo = $this->getMediaManager()->getById($logoId, MediaManager::TYPE_IMAGE);
        if (!$logo instanceof Media && $logoId !== 0) {
            trigger_error("Logo not found, unsetting.");
            $this->set($option, 0, $logoSetting->getLanguageId());

            return $this->getLogo($languageId);
        }
        return $logo;
    }
}