<?php


use Nette\Caching\IStorage;
use Nette\Database\Context;
use Nette\Database\Table\ActiveRow;
use Nette\Security\User;

//TODO onCreatePage create new page or use empty one
class SettingsManager extends Manager {

    const
        TABLE = "settings",
        COLUMN_ID = "settings_id",
        COLUMN_OPTION = "option", COLUMN_OPTION_LENGTH = 60,
        COLUMN_VALUE = "value",
        COLUMN_LANG = "lang_id",

        ACTION_MANAGE_SETTINGS = "settings.manage";

    /**
     * @var \Nette\Caching\Cache
     */
    private $cache;

    protected function init() {
        /** @var IStorage $storage */
        $storage = $this->getContext()->getByType(IStorage::class);
        $this->cache = new Cache($storage, "settings");
        parent::init();
    }


    public function get(string $option, ?Language $language = null):?Setting {

        $langId = $language instanceof Language ? $language->getId() : 0;

        $cacheKey = $this->getCacheKey($option, $langId);

        /** @var Setting $setting */
        $setting = $this->cache->load($cacheKey);

        if ($setting instanceof Setting && $setting->getLanguageId() !== 0) {
            $setting->setLanguage($this->getLanguageManager()->getById($langId));
        }

        return $setting;
    }

    public function getOrGlobal(string $option, Language $language): Setting {
        $local = $this->get($option, $language);

        /* if found */
        if ($local instanceof Setting)
            return $local;

        return $this->get($option);
    }

    public function set(string $option, string $value, ?Language $language = null) {
        if ($this->isAllowedOrThrow()) {
            $exists = is_string($this->get($option, $language));

            $data = [
                self::COLUMN_OPTION => $option,
                self::COLUMN_VALUE  => $value,
            ];

            // if exists update
            if ($exists) {
                $this->getDatabase()->table(self::TABLE)->where([
                    self::COLUMN_OPTION => $option,
                ])->update($data);
                // else insert
            } else {
                $this->getDatabase()->table(self::TABLE)->insert($data);
            }
        }
    }

    private function isAllowedOrThrow(): bool {
        if (!$this->getUser()->isAllowed(self::ACTION_MANAGE_SETTINGS))
            throw new Exception("Not enough rights to edit settings");

        return true;
    }

    public function getPageSettings(Language $language): PageSettings {
        return new PageSettings(
            $this->getOrGlobal(PageManager::SETTINGS_SITE_NAME, $language),
            $this->getOrGlobal(PageManager::SETTINGS_GOOGLE_ANALYTICS, $language),
            $this->getOrGlobal(PageManager::SETTINGS_TITLE_SEPARATOR, $language),
            $this->getOrGlobal(PageManager::SETTINGS_LOGO, $language),
            $this->getOrGlobal(PageManager::SETTINGS_LOGO_ALT, $language)
        );
    }

    public function rebuildCache() {
        /** @var ActiveRow $setting */
        $this->cache->clean();
        foreach ($this->getDatabase()->table(self::TABLE)->fetchAll() as $settingRow) {
            $setting = new Setting(
                $settingRow[self::COLUMN_ID],
                $settingRow[self::COLUMN_LANG],
                $settingRow[self::COLUMN_OPTION],
                $settingRow[self::COLUMN_VALUE]
            );
            $this->cache->save($this->getCacheKey($setting->getOption(), $settingRow[self::COLUMN_LANG]), $setting);
        }
    }

    private function getCacheKey(string $option, int $langId): string {
        return $option . "_" . $langId;
    }

}