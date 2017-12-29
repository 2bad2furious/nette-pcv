<?php


class HeaderPageControl extends BaseControl {
    public function render(Page $page) {
        $header = $this->getHeaderManager()->getRoot($page->getLang(), $page);
        $this->template->header = $header;
        $this->template->render();
    }
}