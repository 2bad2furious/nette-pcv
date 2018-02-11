<?php


class FooterPageControl extends BaseControl {
    /**
     * @var PageWrapper
     */
    private $page;

    public function __construct(PageWrapper $page, BasePresenter $presenter, $name) {
        parent::__construct($presenter, $name);
        $this->page = $page;
    }


    public function render() {
        $this->template->page = $this->page;
        $this->template->languages = $this->getLanguageManager()->getAvailableLanguages();
        $this->template->render();
    }
}