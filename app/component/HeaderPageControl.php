<?php


class HeaderPageControl extends BaseControl {
    public function render(Page $page) {
        $header = $this->getHeaderManager()->getRoot($page->getLang(), $page);
        $this->template->header = $header;
        $this->template->render();
    }

    private function getHeaderManager(): IHeaderManager {
        return $this->getPresenter()->context->getByType(IHeaderManager::class);
    }
}