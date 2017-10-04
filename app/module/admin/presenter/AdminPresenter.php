<?php


namespace adminModule;


use BasePresenter;
use SettingsManager;

abstract class AdminPresenter extends BasePresenter {
    public function checkRequirements($element) {
        parent::checkRequirements($element); // TODO: Change the autogenerated stub
        $this->setAdminLanguage();
    }

    protected function setAdminLanguage() {

        /** @var \UserIdentity $identity */
        $identity = $this->getUser()->getIdentity();

        $this->translator->setLocale($identity->getCurrentLanguage());
    }

    public function beforeRender() {
        $translator = $this->translator;
        $this->template->page_title = $translator->translate($this->setPageTitle());
        $this->template->admin = $translator->translate("admin.page.global.title");
        $this->template->site_title = $this->getSettingsManager()->getPageSettings($this->getLanguageManager()->getByCode($this->locale))->getSiteName()->getValue();
        //$this->template->site_title = $this->getSettingsManager()->getPageSettings();
        $this->template->logo = $translator->translate("global.site.logo");
        $this->template->logo_alt = $translator->translate("global.site.logo_alt");
        parent::beforeRender();
    }

    public function createComponentAdminHeader(string $name) {
        return new \AdminHeaderControl($this, $name);
    }

    public function createComponentAdminFooter(string $name) {
        return new \FooterPageControl($this, $name);
    }

    protected function getSettingsManager(): \SettingsManager {
        return $this->context->getByType(SettingsManager::class);
    }

    protected abstract function setPageTitle(): string;
}