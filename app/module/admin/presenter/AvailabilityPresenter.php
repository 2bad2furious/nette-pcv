<?php


namespace adminModule;


class AvailabilityPresenter extends AdminPresenter {

    const KEY = "key",
        VALUE = "val",
        LANGUAGE = "language",
        URL_KEY = "url";

    const KEYS = [
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
        }
    }

    private function checkUrl() {
        $language = $this->getLanguageManager()->getByCode($this->getParameter(self::LANGUAGE));
        $url = $this->getParameter(self::KEY);
        $id = $this->getParameter(self::ID);
        $result = $this->getPageManager()->isUrlAvailable($url, $language, $id);
        $this->sendJson($result);
    }
}