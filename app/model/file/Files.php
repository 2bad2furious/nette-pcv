<?php


abstract class File {

    const BY_TYPE = [
        IFileManager::TYPE_IMAGE => Image::class,
        IFileManager::TYPE_OTHER => OtherFile::class,
    ];

    /**
     * @param int $type
     * @param int $id
     * @param string $src
     * @return File
     * @throws InvalidArgumentException
     */
    public static function createByType(int $type, int $id, string $src): File {
        if (isset(self::BY_TYPE[$type])) {
            $className = self::BY_TYPE[$type];
            return new $className($id, $src);
        }
        throw new InvalidArgumentException("Invalid type $type");
    }

    private $id;
    private $src;

    /**
     * File constructor.
     * @param int $id
     * @param string $src
     */
    public final function __construct(int $id, string $src) {
        $this->id = $id;
        $this->src = $src;
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
    public function getSrc(): string {
        return $this->src;
    }

    public function getWholeSrc(): string {
        return FileManager::UPLOAD_DIRECTORY . "/" . $this->getSrc();
    }

    public abstract function getType(): int;

    public function isImage(): int {
        return $this instanceof Image;
    }
}

class Image extends File {

    public function getType(): int {
        return IFileManager::TYPE_IMAGE;
    }
}

class OtherFile extends File {

    public function getType(): int {
        return IFileManager::TYPE_OTHER;
    }
}