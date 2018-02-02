<?php


namespace adminModule;


use BasePresenter;
use Nette\Http\SessionSection;
use Tracy\Debugger;

abstract class AdminPresenter extends BasePresenter {

    const ADMIN_LOCALES = ["en_US"];

    public static function getDefaultLocale(): string {
        return self::ADMIN_LOCALES[0];
    }

    protected function checkCurrentIdentity() {
        $identity = $this->getUserIdentity();
        if ($identity instanceof \UserIdentity && $identity->getCurrentLanguage() !== $currentLocale = $this->getAdminLocale()) {
            $this->getUser()->login($this->getUserManager()->saveCurrentLanguage($identity->getId(), $currentLocale));
        }
        return parent::checkCurrentIdentity();
    }


    protected function onBadBadRole(array $allowedRoles, int $currentRole) {
        Debugger::log($allowedRoles);
        Debugger::log($currentRole);
        /* sets failed url to redirect to after login */
        $this->getCustomSession()->offsetSet("url", $this->getHttpRequest()->getUrl());
        $this->getUser()->logout(true);
        $this->addError("Invalid role for selected action.");
        $this->redirect(302, "Default:Default");
    }

    protected function getAdminLocale(): string {
        $locale = $this->locale;
        if (!$locale) $locale = $this->translator->getLocale();

        if (!in_array($locale, self::ADMIN_LOCALES))
            $locale = self::getDefaultLocale();

        return $locale;
    }

    /**
     * @return SessionSection
     */
    protected function getCustomSession(): SessionSection {
        return $this->getSession("custom");
    }

    /**
     * @throws \LanguageByCodeNotFound
     * @throws \LanguageByIdNotFound
     */
    public function beforeRender() {
        $translator = $this->translator;
        $language = $this->getLocaleLanguage();
        $this->template->locale = $language->getCode();

        $this->template->page_subtitle = $translator->translate($this->setPageSubtitle());
        $title = $translator->translate($this->setPageTitle());
        $this->template->page_title = $title . " - " . $translator->translate("admin.global.title");
        $this->template->title = $title;

        $this->template->presenter = $this->getPresenterShortname();
        $this->template->action = $this->getAction();

        $this->payload->title = $this->template->page_title;

        if ($this->getReferer() && $this->isAjax()) {
            try {
                if ($this->isComingFromDifferentPresenter()) {
                    $this->redrawContent();
                    $this->redrawHeader();
                }
            } catch (\InvalidState $exception) {
                Debugger::log($exception);
            }
        }

        parent::beforeRender();
    }

    public function createComponentAdminHeader(string $name) {
        return new \AdminHeaderControl($this, $name);
    }

    public function createComponentAdminFooter(string $name) {
        return new \AdminFooterControl($this, $name);
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

    public function createComponentAdminAdminBar(string $name): \AdminAdminBarControl {
        return new \AdminAdminBarControl($this, $name);
    }
}