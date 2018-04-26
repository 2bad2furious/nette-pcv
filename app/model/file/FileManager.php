<?php


use Nette\Database\IRow;

class FileManager extends Manager implements IFileManager {

    const TABLE = "media",
        COLUMN_ID = "media_id",
        COLUMN_TYPE = "type",
        COLUMN_SRC = "src", COLUMN_SRC_LENGTH = 255;

    const UPLOAD_DIRECTORY = "uploads";

    const BY_TYPE = [
        self::TYPE_IMAGE => [
            "image/jpeg", "image/x-citrix-jpeg", "image/x-citrix-png", "image/png", "image/gif", "image/pjpeg", "image/svg+xml",
        ],
    ];

    private static $mimes;

    private static function getMimes(): \Mimey\MimeTypes {
        return self::$mimes instanceof \Mimey\MimeTypes ? self::$mimes : self::$mimes = new \Mimey\MimeTypes;
    }

    private static function getType(string $contentType): int {
        foreach (self::BY_TYPE as $type => $contentTypes) {
            if (in_array($contentType, $contentTypes))
                return $type;
        }
        return self::TYPE_OTHER;
    }

    /**
     * @return File[]
     * @throws FileNotFoundById
     */
    public function getAvailableImages(): array {
        $available = $this->getDatabase()->table(self::TABLE)->where([
            self::COLUMN_TYPE => self::TYPE_IMAGE,
        ])->fetchAll();
        $availableImages = [];
        foreach ($available as $image) {
            $availableImages[$image[self::COLUMN_ID]] = $this->getById($image[self::COLUMN_ID]);
        }
        return $availableImages;
    }


    private function createFromDbRow(IRow $row): File {
        return File::createByType($row[self::COLUMN_TYPE], $row[self::COLUMN_ID], $row[self::COLUMN_SRC]);
    }

    /**
     * @param int $id
     * @param int|null $desiredType
     * @param bool $throw
     * @return File|null
     * @throws FileNotFoundById
     */
    public function getById(int $id, ?int $desiredType = null, bool $throw = true): ?File {
        $cached = $this->getCache()->load($id, function () use ($id) {
            return $this->getFromDbById($id);
        });

        if (!$cached instanceof File && $throw)
            throw new FileNotFoundById($id);

        if (is_int($desiredType) && $cached instanceof File && $cached->getType() !== $desiredType)
            throw new FileNotFoundWithRightType($id, $desiredType, $cached->getType());

        return $cached instanceof File ? $cached : null;
    }


    private function getCache(): Cache {
        static $cache = null;
        return $cache instanceof Cache ? $cache : $cache = new Cache($this->getDefaultStorage(), "media");
    }

    /**
     * @param $id
     * @return false|File
     */
    private function getFromDbById(int $id) {
        $data = $this->getDatabase()->table(self::TABLE)
            ->wherePrimary($id)
            ->fetch();
        return $data instanceof IRow ? $this->createFromDbRow($data) : false;
    }

    /**
     * @param \Nette\Http\FileUpload $fileUpload
     * @return File
     * @throws Throwable
     */
    public function add(\Nette\Http\FileUpload $fileUpload): File {
        $sanitizedName = $fileUpload->getSanitizedName();
        if (!strlen($sanitizedName) > $this->getMaxNameLength())
            throw new InvalidArgumentException("Too long");//TODO custom exception

        $freeName = $this->getFreeName($sanitizedName);

        $contentType = $fileUpload->getContentType();
        $type = $this::getType($contentType);

        $wwwDir = $this->getContext()->getParameters()["wwwDir"];
        $uploadDir = $wwwDir . "/" . self::UPLOAD_DIRECTORY;
        $fileUpload->move($uploadDir . "/" . $freeName);


        $file = $this->runInTransaction(function () use ($freeName, $type) {
            $id = $this->getDatabase()->table(self::TABLE)->insert([
                self::COLUMN_SRC  => $freeName,
                self::COLUMN_TYPE => $type,
            ])->getPrimary();


            $file = $this->getFromDbById($id);

            $this->uncache($file);

            return $file;
        });

        return $file;
    }


    //FileUpload:76
    public static function sanitizeName(string $name): string {
        return trim(Nette\Utils\Strings::webalize($name, '.', false), '.-');
    }

    private function existsByName(string $sanitizedName): bool {
        return $this->getDatabase()->table(self::TABLE)->where([self::COLUMN_SRC => $sanitizedName])->fetch() instanceof IRow;
    }

    private function getFreeName(string $sanitizedName, int $i = 0) {
        $length = strlen($sanitizedName);
        if ($length > $this->getMaxNameLength() && $i) throw new InvalidArgumentException("Too long");//TODO custom exception
        $firstDotPos = strpos($sanitizedName, ".") ?: $length - 1;
        $extension = substr($sanitizedName, $firstDotPos);
        $name = substr($sanitizedName, 0, $firstDotPos);

        if ($i) $name .= "-$i";
        if ($this->existsByName($finalName = $name . $extension)) {
            return $this->getFreeName($sanitizedName, $i + 1);
        }
        return $finalName;
    }

    private function uncache(File $file) {
        $this->uncacheId($file->getId());
    }

    private function uncacheId(int $id) {
        $this->getCache()->remove($id);
    }

    public function getMaxNameLength(): int {
        return self::COLUMN_SRC_LENGTH;
    }

    /**
     * @param int|null $type
     * @param int $page
     * @param int $perPage
     * @param $numOfPages
     * @return File[]
     */
    public function getAll(?int $type, ?int $page, ?int $perPage, &$numOfPages): array {

        $data = $this->getDatabase()->table(self::TABLE);

        if (is_int($type))
            $data->where([self::COLUMN_TYPE => $type]);

        if ($perPage > 0 && $page > 0) {
            $result = $data->page($page, $perPage, $numOfPages);
        } else if ($perPage > 0) {
            $result = $data->limit($perPage);
        } else $result = $data;

        return array_map(function (IRow $row) use ($type) {
            return $this->getById($row[self::COLUMN_ID], $type);
        }, $result->fetchAll());
    }
}