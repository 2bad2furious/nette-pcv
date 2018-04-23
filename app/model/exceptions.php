<?php


class InvalidState extends Exception {
}

class CannotDeleteLastLanguage extends Exception {
}

abstract class NotFound extends Exception {
    public function __construct($message = "", ?Throwable $previous = null) {
        parent::__construct($message, 404, $previous);
    }

}

abstract class NotFoundById extends NotFound {
    abstract protected function getName(): string;

    private $id;

    public function __construct(int $id, ?Throwable $previous = null) {
        $this->id = $id;
        parent::__construct($this->getName() . " not found with id: " . $id, $previous);
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

    public function __construct(string $option, Throwable $previous = null) {
        $this->option = $option;
        parent::__construct("Setting $option not found", $previous);
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

class LanguageByCodeNotFound extends NotFound {
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
        parent::__construct("Page pageId=$id - LanguageId=$languageId not found", $previous);
    }

}

class HeaderNotFound extends NotFoundById {

    protected function getName(): string {
        return "Header";
    }
}

class HomePageNotSet extends PageNotFound {

}

class FileNotFoundById extends NotFoundById {

    protected function getName(): string {
        return "File";
    }
}

class FileNotFoundWithRightType extends FileNotFoundById {
    /**
     * @var int
     */
    private $desiredType;
    /**
     * @var int
     */
    private $foundType;

    public function __construct(int $id, int $desiredType, int $foundType, Throwable $previous = null) {
        $this->desiredType = $desiredType;
        $this->foundType = $foundType;
        parent::__construct($id, $previous);
    }

    /**
     * @return int
     */
    public function getDesiredType(): int {
        return $this->desiredType;
    }

    /**
     * @return int
     */
    public function getFoundType(): int {
        return $this->foundType;
    }
}

class FileNotFoundByName extends NotFound {
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name, ?Throwable $previous = null) {

        parent::__construct("File $name not foudn", $previous);
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getName(): string {
        return $this->name;
    }
}

class FileAlreadyExists extends Exception {
    public function __construct(?Throwable $previous = null) {
        parent::__construct("File already exists", 500, $previous);
    }
}

class SliderByIdNotFound extends NotFoundById {

    protected function getName(): string {
        return "Slider";
    }
}

class SlideByIdNotFound extends NotFoundById {

    protected function getName(): string {
        return "Slide";
    }
}

class UserIdentityByIdNotFound extends NotFoundById {

    protected function getName(): string {
        return "UserIdentity ";
    }
}