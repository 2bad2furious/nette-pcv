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
     * SettingWrapper constructor.
     * @param Setting $setting
     * @param ILanguageManager $languageManager
     */
    public function __construct(Setting $setting, ILanguageManager $languageManager) {
        $this->setting = $setting;
    }

    public function __call(string $name,array $arguments) {
        return call_user_func_array([$this->getSetting(),$name],$arguments);
    }

    private function getSetting() {
        return $this->setting;
    }
}