<?php


use Nette\Application\IRouter;
use Nette\Application\Request;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Http\Url;
use Nittro\Bridges\NittroUI\Presenter;

abstract class BasePresenter extends Presenter {
    const SOMETHING_WENT_WRONG = "messages.message.something_went_wrong";

    /** @var  LanguageManager */
    private $languageManager;

    /** @var  PageManager */
    private $pageManager;

    /** @var  SettingsManager */
    private $settingsManager;

    /** @var  HeaderManager */
    private $headerManager;

    /** @var  UserManager */
    private $userManager;

    /** @var  TagManager */
    private $tagManager;

    /** @var MediaManager */
    private $mediaManager;

    /** @var Language|null */
    private $localeLang;

    /** @var FormFactory */
    private $formFactory;

    /** @persistent */
    public $locale;

    /** @var \Kdyby\Translation\Translator @inject */
    public $translator;

    public function checkRequirements($element) {
        $this->checkCurrentIdentity();
        $this->checkRoles();
        parent::checkRequirements($element);
    }

    public function startup() {
        $this->checkRefererAndDisallowAjax();
        $this->setDefaultSnippets(["content"]);
        parent::startup();
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
                $this->somethingWentWrong();
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

    protected function somethingWentWrong() {
        $this->addError(self::SOMETHING_WENT_WRONG);
    }

    protected function addError(string $message) {
        $this->flashMessage($message, 'error');
    }

    protected function addWarning(string $message) {
        $this->flashMessage($message, 'warning');
    }

    protected final function getUserManager(): UserManager {
        if (!$this->userManager instanceof UserManager) {
            $this->userManager = $this->context->getByType(UserManager::class);
        }
        return $this->userManager;
    }


    protected final function getLanguageManager(): LanguageManager {
        if (!$this->languageManager instanceof LanguageManager) {
            $this->languageManager = $this->context->getByType(LanguageManager::class);
        }
        return $this->languageManager;
    }

    protected final function getHeaderManager(): HeaderManager {
        if (!$this->headerManager instanceof HeaderManager) {
            $this->headerManager = $this->context->getByType(HeaderManager::class);
        }
        return $this->headerManager;
    }

    protected final function getTagManager(): TagManager {
        if (!$this->tagManager instanceof TagManager) {
            $this->tagManager = $this->context->getByType(TagManager::class);
        }
        return $this->tagManager;
    }

    protected final function getPageManager(): PageManager {
        if (!$this->pageManager instanceof PageManager) {
            $this->pageManager = $this->context->getByType(PageManager::class);
        }
        return $this->pageManager;
    }

    protected final function getSettingsManager(): SettingsManager {
        if (!$this->settingsManager instanceof SettingsManager) {
            $this->settingsManager = $this->context->getByType(SettingsManager::class);
        }
        return $this->settingsManager;
    }


    protected final function getMediaManager(): MediaManager {
        if (!$this->mediaManager instanceof MediaManager) {
            $this->mediaManager = $this->context->getByType(MediaManager::class);
        }
        return $this->mediaManager;
    }

    protected final function getFormFactory(): FormFactory {
        if (!$this->formFactory instanceof FormFactory) {
            $this->formFactory = $this->context->getByType(FormFactory::class);
        }
        return $this->formFactory;
    }


    private function checkRefererAndDisallowAjax() {
        if ($referer = $this->getReferer()) {
            $match = $this->getRouter()->match($request = new \Nette\Http\Request($script = new \Nette\Http\UrlScript($referer)));
            dump($match, $request, $script, $referer);
        }
    }

    private function getRouter(): IRouter {
        return $this->context->getByType(IRouter::class);
    }

}