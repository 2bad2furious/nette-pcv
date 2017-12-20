<?php

use Nette\Database\Table\ActiveRow;

class SettingsManager extends Manager {

    const
        TABLE = "settings",
        COLUMN_ID = "settings_id",
        COLUMN_OPTION = "option", COLUMN_OPTION_LENGTH = 60,
        COLUMN_VALUE = "value",
        COLUMN_LANG = "lang_id",

        ACTION_MANAGE_SETTINGS = "settings.manage";


    public function get(string $option, ?Language $language = null):?Setting {

        $langId = $language instanceof Language ? $language->getId() : 0;

        $cacheKey = $this->getCacheKey($option, $langId);

        /** @var Setting $setting */
        $setting = $this->getCache()->load($cacheKey);

        if ($setting instanceof Setting && $setting->getLanguageId() !== 0) {
            $setting->setLanguage($this->getLanguageManager()->getById($langId));
        }

        return $setting;
    }

    public function set(string $option, string $value, ?Language $language = null, bool $createIfNotFound = false) {
        if (strlen($option) > self::COLUMN_OPTION_LENGTH) throw new Exception(sprintf("Option must be at most %s characters long", self::COLUMN_OPTION_LENGTH));
        if ($this->isAllowedOrThrow()) {
            $exists = $this->get($option, $language);

            $langId = $language instanceof Language ? $language->getId() : 0;

            $data = [
                self::COLUMN_OPTION => $option,
                self::COLUMN_VALUE  => $value,
                self::COLUMN_LANG   => $langId,
            ];
            dump($option, $langId, $exists, $createIfNotFound);
            // if exists update
            if ($exists instanceof Setting) {
                $settingId = $exists->getId();
                $this->getDatabase()
                    ->table(self::TABLE)
                    ->where([
                        self::COLUMN_OPTION => $option,
                        self::COLUMN_LANG   => $langId,
                    ])->update($data);
            } // else insert
            else if ($createIfNotFound) $settingId = $this->getDatabase()->table(self::TABLE)->insert($data)->getPrimary();
            else throw new Exception("Setting not found");

            $this->getCache()->save($this->getCacheKey($option, $langId), new Setting(
                $settingId, $langId, $option, $value
            ));
        }
    }

    private function isAllowedOrThrow(): bool {
        if (!$this->getUser()->isAllowed(self::ACTION_MANAGE_SETTINGS))
            throw new Exception("Not enough rights to edit settings");

        return true;
    }

    private function getLogo(Language $language):?Media {
        $setting = $this->get(PageManager::SETTINGS_LOGO, $language);

        if ((int)$setting->getValue() === 0) $setting = $this->get(PageManager::SETTINGS_LOGO);

        $logoId = (int)$setting->getValue();
        return $this->getMediaManager()->getById($logoId);
    }

    public function getPageSettings(Language $language): PageSettings {
        return new PageSettings(
            $this->getSiteName($language),
            $this->getGoogleAnalytics($language),
            $this->getTitleSeparator($language),
            $this->getLogo($language),
            $this->getFavicon($language)
        );
    }

    public function rebuildCache() {
        /** @var ActiveRow $setting */
        $this->getCache()->clean();
        foreach ($this->getDatabase()->table(self::TABLE)->fetchAll() as $settingRow) {
            $setting = new Setting(
                $settingRow[self::COLUMN_ID],
                $settingRow[self::COLUMN_LANG],
                $settingRow[self::COLUMN_OPTION],
                $settingRow[self::COLUMN_VALUE]
            );
            $this->getCache()->save($this->getCacheKey($setting->getOption(), $settingRow[self::COLUMN_LANG]), $setting);
        }
    }

    private function getCacheKey(string $option, int $langId): string {
        return $option . "_" . $langId;
    }

    public function getSiteName(Language $language): string {
        return $this->getLocalOrGlobal(PageManager::SETTINGS_SITE_NAME, $language)->getValue();
    }

    private function getGoogleAnalytics(Language $language): string {
        return $this->getLocalOrGlobal(PageManager::SETTINGS_GOOGLE_ANALYTICS, $language)->getValue();
    }

    private function getTitleSeparator(Language $language): string {
        return $this->getLocalOrGlobal(PageManager::SETTINGS_TITLE_SEPARATOR, $language)->getValue();
    }

    /**
     * Gets local or global setting, works only for string values
     * @param string $option
     * @param Language $language
     * @return Setting
     */
    private function getLocalOrGlobal(string $option, Language $language): Setting {
        $setting = $this->get($option, $language);
        if (!$setting->getValue()) $setting = $this->get($option);
        return $setting;
    }

    private function getCache(): Cache {
        static $cache = null;
        return $cache instanceof Cache ? $cache : $cache = new Cache($this->getDefaultStorage(), "settings");
    }

    private function getFavicon(Language $language):?Media {
        $setting = $this->get(PageManager::SETTINGS_LOGO, $language);

        if ((int)$setting->getValue() === 0) $setting = $this->get(PageManager::SETTINGS_FAVICON);

        $faviconId = (int)$setting->getValue();
        return $this->getMediaManager()->getById($faviconId);
    }
}