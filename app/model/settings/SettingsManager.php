<?php


use Nette\Caching\IStorage;
use Nette\Database\Context;
use Nette\Database\Table\ActiveRow;
use Nette\Security\User;

class SettingsManager {

    const
        TABLE = "settings",
        COLUMN_ID = "settings_id",
        COLUMN_OPTION = "option", COLUMN_OPTION_LENGTH = 60,
        COLUMN_VALUE = "value",
        COLUMN_LANG = "lang_id",

        ACTION_MANAGE_SETTINGS = "settings.manage";

    /** @var  Context */
    private $database;

    /**
     * @var User
     */
    private $user;
    /**
     * @var \Nette\Caching\Cache
     */
    private $cache;

    /**
     * SettingsManager constructor.
     * @param IStorage $storage
     * @param Context $database
     * @param User $user
     */
    public function __construct(IStorage $storage, Context $database, User $user) {
        $this->database = $database;
        $this->user = $user;

        $this->cache = new \Nette\Caching\Cache($storage, "settings");
    }

    public function get(string $option, ?Language $language = null):?Setting {

        $langId = $language instanceof Language ? $language->getId() : 0;

        $cacheKey = $option . "_" . $langId;

        $cached = $this->cache->load($cacheKey);

        if (!$cached instanceof Setting && !is_bool($cached)) {

            $row = $this->database->table(self::TABLE)
                ->where([
                    self::COLUMN_OPTION => $option,
                    self::COLUMN_LANG   => $langId,
                ])
                ->fetch();

            if (!$row instanceof ActiveRow) $setting = false;
            else $setting = $this->createFromRow($row, $language);

            $cached = $this->cache->save($cacheKey, $setting);
        }
        return $cached instanceof Setting ? $cached : null;
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
                $this->database->table(self::TABLE)->where([
                    self::COLUMN_OPTION => $option,
                ])->update($data);
                // else insert
            } else {
                $this->database->table(self::TABLE)->insert($data);
            }
        }
    }

    private function isAllowedOrThrow(): bool {
        if (!$this->user->isAllowed(self::ACTION_MANAGE_SETTINGS))
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

    /**
     * @param ActiveRow $row
     * @return Setting
     */
    private function createFromRow(ActiveRow $row, ?Language $language): Setting {
        return new Setting(
            $row[self::COLUMN_ID],
            $language,
            $row[self::COLUMN_OPTION],
            $row[self::COLUMN_VALUE]
        );
    }
}