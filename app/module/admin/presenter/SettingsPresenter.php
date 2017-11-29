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

    public function actionRebuild() {
        try {
            $pm = $this->getPageManager();
            $pm->rebuildCache();
            $lm = $this->getLanguageManager();
            $lm->rebuildCache();
            $sm = $this->getSettingsManager();
            $sm->rebuildCache();
            $hm = $this->getHeaderManager();
            $hm->rebuildCache();

            $this->addSuccess("admin.settings.rebuild.success");
        } catch (Exception $exception) {
            Debugger::log($exception);
            $this->somethingWentWrong();
        }
        $this->redirect(":default");
    }

    public function createComponentSettingsEditForm() {
        $form = $this->getFormFactory()->createSettingsEditForm();
        $form->onSuccess[] = function (Form $form) {
            $values = $form->getValues(true);
            foreach (self::TABLE as $k => $v) {
                $this->getSettingsManager()->set($v, $values[$k], null, false);
            }
            $this->addSuccess("admin.settings.edit.success");
            $this->redirect(302, "this");
        };
        return $form;
    }
}