<?php


class Language {
    private $id;
    private $code;
    private $friendly;
    private $siteName;
    private $titleSeparator;
    private $ga;
    private $logo_id;
    private $favicon_id;
    private $homepage_id;
    private $errorpage_id;


    /**
     * Language constructor.
     * @param int $id
     * @param string $code
     * @param string $friendly
     * @param string $siteName
     * @param string $titleSeparator
     * @param string $ga
     * @param int|null $logo_id
     * @param int|null $favicon_id
     * @param int|null $homepage_id
     * @param int|null $errorpage_id
     */
    public function __construct(int $id, string $code, string $friendly, string $siteName, string $titleSeparator, string $ga, ?int $logo_id, ?int $favicon_id, ?int $homepage_id, ?int $errorpage_id) {
        $this->id = $id;
        $this->code = $code;
        $this->friendly = $friendly;
        $this->ga = $ga;
        $this->logo_id = $logo_id;
        $this->favicon_id = $favicon_id;
        $this->homepage_id = $homepage_id;
        $this->errorpage_id = $errorpage_id;
        $this->siteName = $siteName;
        $this->titleSeparator = $titleSeparator;
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getCode(): string {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getFriendly(): string {
        return $this->friendly;
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
    public function getGa(): string {
        return $this->ga;
    }

    /**
     * @return int|null
     */
    public function getLogoId(): ?int {
        return $this->logo_id;
    }

    /**
     * @return int|null
     */
    public function getFaviconId(): ?int {
        return $this->favicon_id;
    }

    /**
     * @return int|null
     */
    public function getHomepageId(): ?int {
        return $this->homepage_id;
    }

    /**
     * @return int|null
     */
    public function getErrorpageId(): ?int {
        return $this->errorpage_id;
    }
}