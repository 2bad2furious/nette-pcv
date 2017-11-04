<?php


namespace adminModule;


use BasePresenter;
use Nette\Http\SessionSection;
use SettingsManager;
use Tracy\Debugger;

abstract class AdminPresenter extends BasePresenter {

    protected function getCallbackWhenBadRole(array $allowedRoles, int $currentRole): callable {
        Debugger::log($allowedRoles);
        Debugger::log($currentRole);
        dump($allowedRoles, $currentRole);
        return function () {
            /* sets failed url to redirect to after login */
            $this->getCustomSession()->offsetSet("url", $this->getHttpRequest()->getUrl());
            $this->flashMessage("Invalid role for selected action.");
            $this->redirect(302, "Default:Default");
        };
    }

    /**
     * @return SessionSection
     */
    protected function getCustomSession(): SessionSection {
        return $this->getSession("custom");
    }

    public function beforeRender() {
        $translator = $this->translator;
        $language = $this->getLocaleLanguage();
        $this->template->locale = $language->getCode();
        $pageSettings = $this->getSettingsManager()->getPageSettings($language);
        $this->template->page_subtitle = $translator->translate($this->setPageSubtitle());
        $this->template->page_title = $translator->translate($this->setPageTitle());
        $this->template->admin = $translator->translate("admin.global.title");
        $this->template->site_title = $pageSettings->getSiteName()->getValue();
        $this->template->logo = $pageSettings->getLogo()->getValue();
        $this->template->logo_alt = $pageSettings->getLogoAlt()->getValue();
        parent::beforeRender();
    }

    public function createComponentAdminHeader(string $name) {
        return new \AdminHeaderControl($this, $name);
    }

    public function createComponentAdminFooter(string $name) {
        return new \AdminFooterControl($this, $name);
    }

    protected function getSettingsManager(): \SettingsManager {
        return $this->context->getByType(SettingsManager::class);
    }

    protected function getPresenterShortname(): string {
        $shortname = $title = (string)$this->getName();
        $explosion = explode(":", $title);
        $length = count($explosion);
        if ($length == 2) $shortname = $explosion[1];

        return lcfirst($shortname);
    }

    protected function setPageTitle(): string {
        return "admin." . $this->getPresenterShortname() . ".title";
    }

    protected function setPageSubtitle(): string {
        return "admin." . $this->getPresenterShortname() . "." . $this->getAction() . ".title";
    }

    /**
     * Override this if you want to display any other message
     * @return string[]
     */
    protected function getMessagesWhenBadRole(): array {
        return [];
    }

}