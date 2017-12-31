<?php


namespace adminModule;


use Nette\Application\UI\Form;
use Nette\Neon\Exception;
use Tracy\Debugger;

class SettingsPresenter extends AdminPresenter {

    const TABLE = [
        \FormFactory::SETTINGS_EDIT_TITLE_SEPARATOR          => \PageManager::SETTINGS_TITLE_SEPARATOR,
        \FormFactory::SETTINGS_EDIT_DEFAULT_SITE_TITLE       => \PageManager::SETTINGS_SITE_NAME,
        \FormFactory::SETTINGS_EDIT_DEFAULT_GOOGLE_ANALYTICS => \PageManager::SETTINGS_GOOGLE_ANALYTICS,
        \FormFactory::SETTINGS_EDIT_DEFAULT_LANGUAGE_NAME    => \LanguageManager::SETTINGS_DEFAULT_LANGUAGE,
        \FormFactory::SETTINGS_EDIT_LOGO                     => \PageManager::SETTINGS_LOGO,
    ];

    public function actionDefault() {
        /*


        $this->sendJson("success");*/

    }

    protected function getAllowedRoles(): array {
        return \UserManager::ROLES_USER_ADMINISTRATION;
    }

    public function actionClean() {
        $this->commonTryCall(function () {
            $pm = $this->getPageManager();
            $pm->cleanCache();
            $lm = $this->getLanguageManager();
            $lm->cleanCache();
            $sm = $this->getSettingsManager();
            $sm->cleanCache();
            $hm = $this->getHeaderManager();
            $hm->cleanCache();
            $um = $this->getUserManager();
            $um->cleanCache();
            //TODO rebuild media and tag cache

            $this->addSuccess("admin.settings.clean.success");
        });
        $this->redirect(":default");
    }

    public function createComponentSettingsEditForm() {
        $form = $this->getFormFactory()->createSettingsEditForm();
        $form->onSuccess[] = function (Form $form) {
            $values = $form->getValues(true);
            foreach (self::TABLE as $k => $v) {
                $this->getSettingsManager()->set($v, $values[$k], null);
            }
            $this->addSuccess("admin.settings.edit.success");
            $this->redirect(302, "this");
        };
        return $form;
    }
}