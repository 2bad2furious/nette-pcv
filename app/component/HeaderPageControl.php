<?php


class HeaderPageControl extends BaseControl {
    public function render(Page $page) {
        $header = $this->getHeaderManager()->getHeader($page->getLang());
        $this->template->header = $header;
        $this->template->render();
    }
}