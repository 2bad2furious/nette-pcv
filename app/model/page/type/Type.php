<?php

/***
 * Like an enum or something
 */
abstract class Type {

    const PAGE_TYPE = PageType::class;
    const POST_TYPE = PostType::class;
    const SECTION_TYPE = SectionType::class;

    const BY_ID = [
        1  => PageType::class,
        0  => PostType::class,
        -1 => SectionType::class,
    ];

    const SCHEMA_URL = "http://schema.org/";

    public abstract function getSchemaUrl(): string;

    public abstract function getOgType(): string;

    /**
     * @param int $number
     * @return Type
     */
    public static function getById(int $number): self {
        $className = self::BY_ID[$number];
        return new $className;
    }
}