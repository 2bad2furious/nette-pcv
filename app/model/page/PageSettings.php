<?php


class PageSettings {
    /**
     * @var Setting
     */
    private $siteName;
    /**
     * @var Setting
     */
    private $googleAnalytics;
    /**
     * @var Setting
     */
    private $titleSeparator;
    /**
     * @var Setting
     */
    private $logo;
    /**
     * @var Setting
     */
    private $logo_alt;

    /**
     * PageSettings constructor.
     * @param Setting $siteName
     * @param Setting $googleAnalytics
     * @param Setting $titleSeparator
     * @param Setting $logo
     * @param Setting $logo_alt
     */
    public function __construct(Setting $siteName, Setting $googleAnalytics, Setting $titleSeparator, Setting $logo, Setting $logo_alt) {
        $this->siteName = $siteName;
        $this->googleAnalytics = $googleAnalytics;
        $this->titleSeparator = $titleSeparator;
        $this->logo = $logo;
        $this->logo_alt = $logo_alt;
    }

    /**
     * @return Setting
     */
    public function getSiteName(): Setting {
        return $this->siteName;
    }

    /**
     * @return Setting
     */
    public function getGoogleAnalytics(): Setting {
        return $this->googleAnalytics;
    }

    /**
     * @return Setting
     */
    public function getTitleSeparator(): Setting {
        return $this->titleSeparator;
    }

    /**
     * @return Setting
     */
    public function getLogo(): Setting {
        return $this->logo;
    }

    /**
     * @return Setting
     */
    public function getLogoAlt(): Setting {
        return $this->logo_alt;
    }
}