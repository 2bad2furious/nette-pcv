<?php

interface ITagManager {
    /**
     * @param int $pageId
     * @param Language $lang
     * @return array
     */
    public function getTagsForPageId(int $pageId, Language $lang): array;
}