<?php

interface IFileManager extends IManager {

    const TYPE_IMAGE = 0,
        TYPE_OTHER = 1;

    const TYPES = [self::TYPE_IMAGE, self::TYPE_OTHER];

    /**
     * @return Image[]
     */
    public function getAvailableImages(): array;

    /**
     * @param int $id
     * @param int|null $desiredType
     * @param bool $throw
     * @return File|null
     * @throws FileNotFoundById
     */
    public function getById(int $id, ?int $desiredType = null, bool $throw = true): ?File;

    /**
     * @param \Nette\Http\FileUpload $fileUpload
     * @return File
     */
    public function add(\Nette\Http\FileUpload $fileUpload): File;

    public function getMaxNameLength(): int;

    public function getAll(?int $type, int $page, int $perPage, &$numOfPages): array;
}