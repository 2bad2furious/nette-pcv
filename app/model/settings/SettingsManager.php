<?php


use Nette\Database\Table\IRow;

class SettingsManager extends Manager implements ISettingsManager {
    const
        TABLE = "settings",
        COLUMN_ID = "settings_id",
        COLUMN_OPTION = "option", COLUMN_OPTION_LENGTH = 60,
        COLUMN_VALUE = "value",
        COLUMN_LANG = "lang_id",

        ACTION_MANAGE_SETTINGS = "settings.manage";

    private static function getLangId(?Language $language): int {
        return $language instanceof Language ? $language->getId() : 0;
    }

    public function get(string $option, ?Language $language = null):?Setting {
        $cached = $this->getCache()->load($cacheKey = $this->getCacheKey($option, $language),
            function () use ($cacheKey, $option, $language) {
                return $this->saveToCache($cacheKey, $this->getFromDb($option, self::getLangId($language)));
            });
        if ($cached instanceof Setting) {
            $cached->setLanguage($this->getLanguageManager()->getById($cached->getLanguageId()));
            return $cached;
        }
        return null;
    }

    public function set(string $option, string $value, ?Language $language = null): Setting {
        $existing = $this->get($option, $language);

        $this->uncache($cacheKey = $this->getCacheKey($option, $language));

        $this->getDatabase()->beginTransaction();
        try {

            $langId = self::getLangId($language);

            $whereData = [
                self::COLUMN_OPTION => $option,
                self::COLUMN_LANG   => $langId,
            ];

            $updateData = [
                self::COLUMN_VALUE => $value,
            ];

            if ($existing instanceof Setting) {
                $this->getDatabase()->table(self::TABLE)
                    ->where($whereData)
                    ->update($updateData);

            } else {
                $insertData = array_merge($updateData, $whereData);
                $this->getDatabase()->table(self::TABLE)
                    ->insert($insertData);

                \Tracy\Debugger::log("Added settings for $cacheKey - $value");
            }

            $this->getDatabase()->commit();
        } catch (Exception $exception) {
            $this->getDatabase()->rollBack();
            throw $exception;
        }

        return $this->get($option, $language);
    }

    public function cleanCache() {
        $this->getCache()->clean();
    }

    public function getPageSettings(Language $language): PageSettings {
        return new PageSettings(
            $this->getLocalOrGlobal(PageManager::SETTINGS_SITE_NAME, $language)->getValue(),
            $this->getLocalOrGlobal(PageManager::SETTINGS_GOOGLE_ANALYTICS, $language)->getValue(),
            $this->getLocalOrGlobal(PageManager::SETTINGS_TITLE_SEPARATOR, $language)->getValue(),
            $this->getLogo($language),
            $this->getFavicon($language)
        );
    }

    private function getFromDb(string $option, int $langId):?Setting {
        $data = $this->getDatabase()
            ->table(self::TABLE)
            ->where([
                self::COLUMN_OPTION => $option,
                self::COLUMN_LANG   => $langId,
            ])->fetch();

        return $data instanceof IRow ? $this->getFromRow($data) : null;
    }

    private function getFromRow(IRow $row): Setting {
        return new Setting($row[self::COLUMN_ID], $row[self::COLUMN_LANG], $row[self::COLUMN_OPTION], $row[self::COLUMN_VALUE]);
    }

    private function getCache(): Cache {
        static $cache = null;
        return $cache instanceof Cache ? $cache : $cache = new Cache($this->getDefaultStorage(), "settings");
    }

    /**
     * Saves either Setting or false to cache
     * @param string $key
     * @param null|Setting $setting
     * @return null|Setting
     */
    private function saveToCache(string $key, ?Setting $setting):?Setting {
        $value = $setting instanceof Setting ? $setting : false;
        $this->getCache()->save($key, function () use ($value) {
            return $value;
        });
        return $setting;
    }

    private function uncache(string $key) {
        $this->getCache()->remove($key);
    }

    private function getCacheKey(string $option, ?Language $language): string {
        return $option . "_" . self::getLangId($language);
    }

    private function getLocalOrGlobal(string $option, Language $language): Setting {
        $local = $this->get($option, $language);
        return ($local->getValue()) ? $local : $this->get($option, null);
    }

    //TODO fix copy-paste
    private function getFavicon(Language $language):?Media {
        $option = PageManager::SETTINGS_FAVICON;
        $faviconSetting = $this->getLocalOrGlobal($option, $language);
        $faviconId = (int)$faviconSetting->getValue();
        $favicon = $this->getMediaManager()->getById($faviconId, MediaManager::TYPE_IMAGE);
        if (!$favicon instanceof Media && $faviconId !== 0) {
            trigger_error("Favicon not found, unsetting.");
            $this->set($option, 0, $faultyLang = $faviconSetting->isGlobal() ? null : $language);

            $this->uncache($this->getCacheKey($option, $faultyLang));

            return $this->getFavicon($language);
        }
        return $favicon;
    }

    private function getLogo(Language $language):?Media {
        $option = PageManager::SETTINGS_LOGO;
        $logoSetting = $this->getLocalOrGlobal($option, $language);
        $logoId = (int)$logoSetting->getValue();
        $logo = $this->getMediaManager()->getById($logoId, MediaManager::TYPE_IMAGE);
        if (!$logo instanceof Media && $logoId !== 0) {
            trigger_error("Logo not found, unsetting.");
            $this->set($option, 0, $faultyLang = $logoSetting->isGlobal() ? null : $language);

            $this->uncache($this->getCacheKey($option, $faultyLang));

            return $this->getLogo($language);
        }
        return $logo;
    }
}