<?php

interface IHeaderManager {

    public function cleanCache();

    /**
     * @param Language $language
     * @param null|Page $currentPage
     * @return array
     */
    public function getHeader(Language $language, ?Page $currentPage = null): array;

    /**
     * @param int $id
     * @return HeaderWrapper|null
     */
    public function getById(int $id):?HeaderWrapper;

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

    public function exists(int $id, int $langId): bool;

    public function changeParentOrPosition(int $headerId, int $parentHeaderId, int $position);

    public function canChangeParent(int $headerId, int $parentHeaderId): bool;
}