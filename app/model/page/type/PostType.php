<?php


class PostType extends Type {

    public function getSchemaUrl(): string {
        return self::SCHEMA_URL . "Article";
    }

    public function getOgType(): string {
        return "article";
    }

    public function __toString(): string {
        return "Post";
    }
}