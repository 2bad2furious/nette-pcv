<?php


use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Http\Url;
use Nittro\Bridges\NittroUI\Presenter;

abstract class BasePresenter extends Presenter {
    const SOMETHING_WENT_WRONG = "messages.message.something_went_wrong";

    /** @var Language|null */
    private $localeLang;

    /** @persistent */
    public $locale;

    /** @var \Kdyby\Translation\Translator @inject */
    public $translator;

    public function checkRequirements($element) {
        $this->checkCurrentIdentity();
        $this->checkRoles();
        parent::checkRequirements($element);
    }

    protected function checkCurrentIdentity() {
        $id = $this->getUser()->getId();
        if (is_int($id)) {
            $newIdentity = $this->getUserManager()->getUserIdentityById($id);

            /* return if the sessioned identity is the same as the one in the db, logging in and out caused some regenerate_session_id issues */
            if ($newIdentity == $this->getUser()->getIdentity()) return;

            if ($newIdentity instanceof UserIdentity) {
                $this->getUser()->login($newIdentity);
            } else {
                $this->flashMessage(self::SOMETHING_WENT_WRONG);
                $this->getUser()->logout(true);
            }
        }
    }

    protected function checkRoles() {
        $allowedRoles = $this->getAllowedRoles();
        $identity = $this->getUser()->getIdentity();
        $currentRole = $identity instanceof UserIdentity ? $identity->getRole() : UserManager::ROLE_GUEST;

        $isInRoles = (in_array($currentRole, $allowedRoles));

        if (!$isInRoles) {
            call_user_func($this->getCallbackWhenBadRole($allowedRoles, $currentRole));
        }
    }

    public function flashMessage($message, $type = 'info') {
        return parent::flashMessage($this->translator->translate($message), $type);
    }

    public function createComponentHeader(string $name) {
        return new \HeaderPageControl($this, $name);
    }

    public function createComponentFooter(string $name) {
        return new \FooterPageControl($this, $name);
    }

    protected function getFormFactory(): FormFactory {
        return $this->context->getByType(FormFactory::class);
    }

    protected function getUserManager(): UserManager {
        return $this->context->getByType(UserManager::class);
    }

    protected function getLanguageManager(): LanguageManager {
        return $this->context->getByType(LanguageManager::class);
    }

    protected function getCallbackWhenBadRole(array $allowedRoles, int $currentRole): callable {
        return function () {
            throw new Exception("Bad rights xd");
        };
    }

    protected function getLocaleLanguage(): ?Language {
        if (!$this->localeLang instanceof Language)
            $this->localeLang = $this->getLanguageManager()->getByCode($this->translator->getLocale());
        return $this->localeLang;
    }

    protected abstract function getAllowedRoles(): array;

    protected function getReferer():?Url {
        $request = $this->getHttpRequest();
        return ($request instanceof Nette\Http\Request) ? $request->getReferer() : null;
    }

    protected function isRefererOk(string $path = "", array $args = []): bool {
        $referer = $this->getReferer();
        if (!$referer instanceof Url) return false;
        $this->absoluteUrls = true;
        $url = new Url($this->link($path, $args));
        $this->absoluteUrls = false;
        return ($referer->getHost() === $url->getHost());
    }
}