<?php

interface IHeaderManager {

    public function cleanCache();

    /**
     * @param Language $language
     * @param null|Page $currentPage
     * @return HeaderPage
     */
    public function getRoot(Language $language, ?Page $currentPage): HeaderPage;

    /**
     * @param int $id
     * @return HeaderPage|null
     */
    public function getById(int $id):?HeaderPage;

    public function addPage(HeaderPage $parent, int $pageId, string $title):int;

    public function addCustom(HeaderPage $parent, string $title, string $url):int;

    public function editPage(HeaderPage $headerPage, int $pageId, string $title);

    public function editCustom(HeaderPage $headerPage, string $title, string $url);

    /**
     * @param int $id
     * @return void
     * @throws InvalidArgumentException
     */
    public function delete(int $id);
}