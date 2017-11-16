<?php


namespace adminModule;


class SettingsPresenter extends AdminPresenter {


    public function actionDefault() {
        $pm = $this->getPageManager();
        $pm->rebuildCache();
        $lm = $this->getLanguageManager();
        $lm->rebuildCache();
        $sm = $this->getSettingsManager();
        $sm->rebuildCache();
        $hm = $this->getHeaderManager();
        $hm->rebuildCache();

        $this->sendJson("success");
    }

    protected function getAllowedRoles(): array {
        return \UserManager::ROLES_ADMIN_ADMINISTRATION;
    }
}