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
     * @return void
     * throws
     */
    public function delete(int $id);

    /**
     * @param int $id
     * @return HeaderPage|null
     */
    public function getById(int $id):?HeaderPage;
}