<?php


class PageSettings {
    private $siteName;
    private $titleSeparator;
    private $googleAnalytics;
    private $logo;
    private $favicon;


    /**
     * PageSettings constructor.
     * @param string $siteName
     * @param string $googleAnalytics
     * @param string $titleSeparator
     * @param Media|null $logo
     * @param Media|null $favicon
     * @throws Exception
     */
    public function __construct(string $siteName, string $googleAnalytics, string $titleSeparator, ?Media $logo,?Media $favicon) {
        $this->siteName = $siteName;
        $this->googleAnalytics = $googleAnalytics;
        $this->titleSeparator = $titleSeparator;
        $this->logo = $logo;
        if($logo instanceof Media && !$logo->isImage()) throw new Exception("Logo not image");
        $this->favicon = $favicon;
        if($favicon instanceof Media && !$favicon->isImage()) throw new Exception("Favicon not image");
    }

    /**
     * @return string
     */
    public function getSiteName(): string {
        return $this->siteName;
    }

    /**
     * @return string
     */
    public function getTitleSeparator(): string {
        return $this->titleSeparator;
    }

    /**
     * @return string
     */
    public function getGoogleAnalytics(): string {
        return $this->googleAnalytics;
    }

    /**
     * @return Media|null
     */
    public function getLogo() {
        return $this->logo;
    }

    public function getFavicon():?Media {
        return $this->favicon;
    }
}