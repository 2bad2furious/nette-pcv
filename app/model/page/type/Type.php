<?php

/***
 * Like an enum or something
 */
abstract class Type {

    const PAGE_TYPE = PageType::class;
    const POST_TYPE = PostType::class;
    const SECTION_TYPE = SectionType::class;

    const BY_ID = [
        PageManager::TYPE_PAGE    => PageType::class,
        PageManager::TYPE_POST    => PostType::class,
        PageManager::TYPE_SECTION => SectionType::class,
    ];

    const SCHEMA_URL = "http://schema.org/";

    public abstract function getSchemaUrl(): string;

    public abstract function getOgType(): string;

    /**
     * @param int $number
     * @return Type|null
     */
    public static function getById(int $number): ?self {
        if (!isset(self::BY_ID[$number])) return null;
        $className = self::BY_ID[$number];
        return new $className;
    }

    public abstract function __toString(): string;
}