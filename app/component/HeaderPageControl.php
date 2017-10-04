<?php


class HeaderPageControl extends BaseControl {
    public function render(Page $page) {
        $header = $this->getHeaderManager()->getRoot($page->getLang(), $page);
        $this->template->header = $header;
        $this->template->render();
    }

    private function getHeaderManager(): HeaderManager {
        return $this->getPresenter()->context->getByType(HeaderManager::class);
    }
}