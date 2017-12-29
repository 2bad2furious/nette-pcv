<?php


class SectionPageControl extends BaseControl {

    public function render(Page $page) {
        if (!$page->isSection()) throw new Exception("Page not a section.");
        $this->template->oc = $page->getContent();
        $this->template->posts = $this->getPageManager()->getSectionPosts($page->getLocalId());
        $this->template->render();
    }
}