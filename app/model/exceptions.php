<?php


class InvalidState extends Exception {
}

class CannotDeleteLastLanguage extends Exception {
}

abstract class NotFound extends Exception {
    public function __construct($message = "", Throwable $previous = null) {
        parent::__construct($message, 404, $previous);
    }

}

abstract class NotFoundById extends NotFound {
    abstract protected function getName(): string;

    private $id;

    public function __construct(int $id, Throwable $previous = null) {
        $this->id = $id;
        parent::__construct($this->getMessage() . " not found with id: " . $id, $previous);
    }

    /**
     * @return int
     */
    public function getId(): int {
        return $this->id;
    }
}

class SettingNotFound extends NotFound {
    private $option;
    private $languageId;

    public function __construct(string $option, int $languageId, Throwable $previous = null) {
        $this->option = $option;
        $this->languageId = $languageId;
        parent::__construct("Setting $option - $languageId not found", $previous);
    }

    public function getOption(): string {
        return $this->option;
    }

    public function getLanguageId(): int {
        return $this->languageId;
    }

}

class LanguageByIdNotFound extends NotFoundById {
    protected function getName(): string {
        return "Language";
    }
}

class LanguageByCodeNotFound extends NotFound{
    private $langCode;

    /**
     * @return string
     */
    public function getLanguageCode(): string {
        return $this->langCode;
    }

    public function __construct(string $langCode, Throwable $previous = null) {
        $this->langCode = $langCode;
        parent::__construct("Language '$langCode' not found", $previous);
    }

}

class PageNotFound extends NotFound {
    private $id;
    private $languageId;
    public function __construct(int $id, int $languageId, Throwable $previous = null) {
        $this->id = $id;
        $this->languageId = $languageId;
        parent::__construct("Page $id - $languageId not found", $previous);
    }

}

class HeaderNotFound extends NotFoundById {

    protected function getName(): string {
        return "Header";
    }
}

class HomePageNotSet extends PageNotFound {

}

