<?php


class SectionPageControl extends BaseControl {

    public function render(Page $page) {
        if (!$page->isSection()) throw new Exception("Page not a section.");
        $this->template->oc = $page->getContent();
        /** @var PageManager $pageManager */
        $pageManager = $this->getPresenter()->context->getByType(PageManager::class);
        $this->template->posts = $pageManager->getSectionPosts($page->getLocalId());
        $this->template->render();
    }
}