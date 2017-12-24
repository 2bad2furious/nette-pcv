<?php

interface IHeaderManager {

    public function cleanCache();

    /**
     * @param Language $language
     * @param null|Page $currentPage
     * @return HeaderPage
     */
    public function getRoot(Language $language, ?Page $currentPage): HeaderPage;

    public function getNextId(): int;
}