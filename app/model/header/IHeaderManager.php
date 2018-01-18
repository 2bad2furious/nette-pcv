<?php

interface IHeaderManager {

    public function cleanCache();

    /**
     * @param int $languageId
     * @return array
     */
    public function getHeader(int $languageId): array;

    /**
     * @param int $id
     * @param bool $throw
     * @return HeaderWrapper|null
     */
    public function getById(int $id, bool $throw = true):?HeaderWrapper;

    public function addPage(int $parentId, int $languageId, int $pageId, string $title): HeaderWrapper;

    public function addCustom(int $parentId, int $languageId, string $title, string $url): HeaderWrapper;

    public function editPage(int $headerId, int $pageId, string $title);

    public function editCustom(int $headerId, string $title, string $url);

    /**
     * @param int $id
     * @return void
     */
    public function delete(int $id);

    /**
     * @param int $id
     * @return void
     * @throws InvalidArgumentException
     */
    public function deleteBranch(int $id);

    public function exists(int $id, ?int $langId = null): bool;

    public function changeParentOrPosition(int $headerId, int $parentHeaderId, int $position);

    public function canChangeParent(int $headerId, int $parentHeaderId): bool;

    public function moveUp(int $headerId);

    public function canBeMovedUp(int $headerId): bool;

    public function moveDown(int $headerId);

    public function canBeMovedDown(int $headerId): bool;

    public function moveLeft(int $headerId);

    public function canBeMovedLeft(int $headerId):bool;

    public function moveRight(int $headerId);

    public function canBeMovedRight(int $headerId):bool;

}