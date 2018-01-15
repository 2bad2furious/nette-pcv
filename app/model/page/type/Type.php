<?php

/***
 * Like an enum or something
 */
abstract class Type {

    const PAGE_TYPE = PageType::class;
    const POST_TYPE = PostType::class;

    const BY_ID = [
        PageManager::TYPE_PAGE => PageType::class,
        PageManager::TYPE_POST => PostType::class,
    ];

    const SCHEMA_URL = "http://schema.org/";

    public abstract function getSchemaUrl(): string;

    public abstract function getOgType(): string;

    /**
     * @param int $id
     * @return Type
     */
    public static function getById(int $id): self {
        $className = self::BY_ID[$id];
        return new $className;
    }

    public abstract function __toString(): string;
}