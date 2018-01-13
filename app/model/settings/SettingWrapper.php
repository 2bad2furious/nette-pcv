<?php

/**
 * Class SettingWrapper
 * @method int getId()
 * @method string getValue()
 * @method string getOption()
 * @method int getLanguageId()
 * @method bool isGlobal()
 */
class SettingWrapper {
    /**
     * @var Setting
     */
    private $setting;
    /**
     * @var ILanguageManager
     */
    private $languageManager;
    /**
     * @var Language
     */
    private $language;

    /**
     * SettingWrapper constructor.
     * @param Setting $setting
     * @param ILanguageManager $languageManager
     */
    public function __construct(Setting $setting, ILanguageManager $languageManager) {
        $this->setting = $setting;
        if ($setting->getLanguageId() === 0) $this->language = false;
        $this->languageManager = $languageManager;
    }

    public function getLanguage():?Language {
        if (!$this->language === null) {
            $this->language = $this->languageManager->getById($this->getLanguageId());
        }
        return $this->language instanceof Language ? $this->language : null;
    }

    public function __call(string $name,array $arguments) {
        return call_user_func_array([$this->getSetting(),$name],$arguments);
    }

    private function getSetting() {
        return $this->setting;
    }
}