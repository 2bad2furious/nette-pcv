<?php


namespace adminModule;


class AvailabilityPresenter extends AdminPresenter {

    const KEY = "key",
        VALUE = "val",
        LANGUAGE = "language",
        URL_KEY = "url",
        LANGUAGE_CODE_KEY = "lang";

    const KEYS = [
        self::LANGUAGE_CODE_KEY,
        self::URL_KEY,
    ];

    const ID = "id";

    protected function getAllowedRoles(): array {
        return \UserManager::ROLES_ADMINISTRATION;
    }

    public function actionDefault() {
        $key = $this->getParameter(self::KEY);
        switch ($key) {
            case self::URL_KEY:
                $this->checkUrl();
                break;
            case self::LANGUAGE_CODE_KEY:
                $this->checkLangCode();
        }
    }

    private function checkUrl() {
        $language = $this->getLanguageManager()->getByCode($this->getLanguageParam());
        $url = $this->getValue();
        $id = $this->getId();
        $result = $this->getPageManager()->isUrlAvailable($url, $language, $id);
        $this->sendJson($result);
    }

    private function checkLangCode() {
        $this->sendJson(!$this->getLanguageManager()->getByCode($this->getValue()) instanceof \Language);
    }

    private function getLanguageParam() {
        return $this->getParameter(self::LANGUAGE);
    }

    private function getId() {
        return $this->getParameter(self::ID);
    }

    private function getValue() {
        return $this->getParameter(self::VALUE);
    }
}