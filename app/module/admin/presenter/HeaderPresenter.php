<?php


namespace adminModule;


use Language;

class HeaderPresenter extends AdminPresenter {


    const LANGUAGE_KEY = "language",
        ID_KEY = "id",
        DIRECTION_KEY = "direction",
        ALL_KEY = "all";

    /** @persistent */
    public $language;

    protected function getAllowedRoles(): array {
        return \UserManager::ROLES_PAGE_MANAGING;
    }

    public function actionDefault() {
        if (!$this->getParameter(self::LANGUAGE_KEY))
            $this->redirect(302, "this",
                [self::LANGUAGE_KEY => $this->getLanguageManager()->getDefaultLanguage()->getCode()]);
    }

    public function renderDefault() {
        $this->template->language = $this->getCurrentLanguage();
        $this->template->languages = $this->getLanguageManager()->getAvailableLanguages( true);

        $this->template->header = $this->getHeaderManager()->getRoot($this->getCurrentLanguage(), null);
        $this->template->nextId = $this->getHeaderManager()->getNextId();
    }

    private function getCurrentLanguage(): Language {
        $code = $this->getParameter(self::LANGUAGE_KEY);
        return $this->getLanguageManager()->getByCode($code);
    }


}