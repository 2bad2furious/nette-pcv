<?php


class PageType extends Type {

    public function getSchemaUrl(): string {
        return self::SCHEMA_URL . "Website";
    }

    public function getOgType(): string {
        return "website";
    }
}