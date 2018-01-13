<?php


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


    /**
     * @return int
     */
    public function getId(): int {
        return $this->setting->getId();
    }

    /**
     * @return string
     */
    public function getOption(): string {
        return $this->setting->getOption();
    }

    /**
     * @return string
     */
    public function getValue(): string {
        return $this->setting->getValue();
    }

    public function getLanguage():?Language {
        if (!$this->language === null) {
            $this->language = $this->languageManager->getById($this->getLanguageId());
        }
        return $this->language instanceof Language ? $this->language : null;
    }

    /**
     * @return int
     */
    public function getLanguageId(): int {
        return $this->setting->getLanguageId();
    }

    public function isGlobal(): bool {
        return $this->getLanguageId() === 0;
    }
}