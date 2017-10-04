<?php


namespace frontModule;


use Exception;

class TestPresenter extends \BasePresenter {
    /**
     * @var \PageManager
     */
    protected $pageManager;

    public function injectPageManager(\PageManager $pageManager){
        if($this->pageManager instanceof \PageManager) throw new Exception("Page Manager already set");
        $this->pageManager = $pageManager;
    }

    public function actionDefault() {
        diedump($this->pageManager->rebuildCache());
    }

    protected function getAllowedRoles(): array {
        return \UserManager::ROLES;
    }
}