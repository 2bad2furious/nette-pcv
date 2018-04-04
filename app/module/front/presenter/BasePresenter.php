<?php


use Nette\Application\IRouter;
use Nette\Application\Request;
use Nette\Http\Session;
use Nette\Http\SessionSection;
use Nette\Http\Url;
use Nittro\Bridges\NittroUI\Presenter;

abstract class BasePresenter extends Presenter {
    const SOMETHING_WENT_WRONG = "something_went_wrong";

    /** @var Language|null */
    private $localeLang;

    /** @persistent */
    public $locale;

    /** @var \Kdyby\Translation\Translator @inject */
    public $translator;
    /** @var  ServiceLoader */
    private $serviceLoader;

    /**
     * @param $element
     * @throws Exception
     * @throws InvalidState
     * @throws \Nette\Application\ForbiddenRequestException
     * @throws \Nette\Security\AuthenticationException
     */
    public function checkRequirements($element) {
        $this->checkCurrentIdentity();
        $this->checkRoles();
        parent::checkRequirements($element);
    }

    /**
     * @throws InvalidState
     */
    public function startup() {
        $this->invalidLinkMode = self::INVALID_LINK_EXCEPTION;
        $this->checkRefererAndDisallowAjax();
        parent::startup();
    }

    protected function checkPaging(int $currentPage, int $maxPage, string $page_key) {
        if ($currentPage < 1) $this->redirect(302, "this", [$page_key => 1]);
        else if ($maxPage !== 0 && $currentPage > $maxPage) $this->redirect(302, "this", [$page_key => $maxPage]);
    }

    /**
     * @throws InvalidState
     * @throws \Nette\Application\AbortException
     * @throws \Nette\Security\AuthenticationException
     */
    protected function checkCurrentIdentity() {
        $id = $this->getUser()->getId();
        if (is_int($id)) {
            try {
                $newIdentity = $this->getAccountManager()->getUserIdentityById($id);
            } catch (UserIdentityByIdNotFound $exception) {
                $this->getUser()->logout(true);
                $this->postGet("this");
            }
            /* return if the sessioned identity is the same as the one in the db, logging in and out caused some regenerate_session_id issues */
            if ($newIdentity == $this->getUserIdentity()) return;

            $this->getUser()->login($newIdentity);
        }
    }

    /**
     * @throws Exception
     */
    protected function checkRoles() {
        $allowedRoles = $this->getAllowedRoles();
        $identity = $this->getUser()->getIdentity();
        $currentRole = $identity instanceof UserIdentity ? $identity->getRole() : IAccountManager::ROLE_GUEST;

        $isInRoles = (in_array($currentRole, $allowedRoles));

        if (!$isInRoles) {
            $this->onBadRole($allowedRoles, $currentRole);
        }
    }

    public function flashMessage($message, $type = 'info') {
        return parent::flashMessage($this->translator->translate($message), $type);
    }

    /**
     * @param array $allowedRoles
     * @param int $currentRole
     * @throws Exception
     */
    protected function onBadRole(array $allowedRoles, int $currentRole) {
        $this->redirect(302,":admin:Default:"); //TODO change on actual role differentiating
        //throw new Exception("Bad rights xd");
    }

    /**
     * @return Language
     * @throws LanguageByCodeNotFound
     * @throws LanguageByIdNotFound
     */
    protected function getLocaleLanguage(): Language {
        if (!$this->localeLang instanceof Language)
            $this->localeLang = $this->getLanguageManager()->getByCode($this->translator->getLocale(), false);
        if (!$this->localeLang instanceof Language)
            $this->localeLang = $this->getLanguageManager()->getDefaultLanguage();
        return $this->localeLang;
    }

    /**
     * @return string
     * @throws InvalidState
     */
    public function getCurrentAdminLocale(): string {
        $identity = $this->getUserIdentity();

        if ($identity instanceof UserIdentity && in_array($language = $identity->getCurrentLanguage(), \adminModule\AdminPresenter::ADMIN_LOCALES)) return $language;

        return \adminModule\AdminPresenter::getDefaultLocale();
    }

    /**
     * @return null|UserIdentity
     * @throws InvalidState
     */
    protected function getUserIdentity(): ?UserIdentity {
        $identity = $this->getUser()->getIdentity();
        if ($identity && !$identity instanceof UserIdentity) throw new InvalidState("UserIdentity not instanceof UserIdentity");
        return $identity instanceof UserIdentity ? $identity : null;
    }


    protected abstract function getAllowedRoles(): array;

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

    public function addSuccess(string $message) {
        $this->flashMessage($message, 'success');
    }

    public function addError(string $message) {
        $this->flashMessage($message, 'error');
    }

    public function addWarning(string $message) {
        $this->flashMessage($message, 'warning');
    }

    protected final function getServiceLoader(): ServiceLoader {
        if (!$this->serviceLoader instanceof ServiceLoader)
            $this->serviceLoader = $this->context->getByType(ServiceLoader::class);
        return $this->serviceLoader;
    }

    protected final function getAccountManager(): IAccountManager {
        return $this->getServiceLoader()->getAccountManager();
    }


    protected final function getLanguageManager(): ILanguageManager {
        return $this->getServiceLoader()->getLanguageManager();
    }

    protected final function getHeaderManager(): IHeaderManager {
        return $this->getServiceLoader()->getHeaderManager();
    }

    protected final function getTagManager(): ITagManager {
        return $this->getServiceLoader()->getTagManager();
    }

    protected final function getPageManager(): IPageManager {
        return $this->getServiceLoader()->getPageManager();
    }

    protected final function getSettingsManager(): ISettingsManager {
        return $this->getServiceLoader()->getSettingsManager();
    }

    protected final function getFileManager(): IFileManager {
        return $this->getServiceLoader()->getFileManager();
    }


    protected final function getFormFactory(): FormFactory {
        return $this->context->getByType(FormFactory::class);
    }


    /**
     * @throws InvalidState
     */
    private function checkRefererAndDisallowAjax() {
        if ($this->isAjax() && $this->getReferer()) {
            $match = $this->getRefererRequest();
            if ($match instanceof Request && $this->isComingFromDifferentModule()) {
                dump($this->getRefererRequest(), $this->getRequest());
                trigger_error("Modules not the same");
                $this->disallowAjax();
            }
            //dump("start", $this->getHttpRequest(), $referer, $oldScript, $script, $request, $script->getBasePath(), $match, "end");
        }
    }

    /**
     * @return bool
     * @throws InvalidState
     */
    protected function isComingFromThis(): bool {
        $match = $this->getRefererRequest();
        dump($match);
        dump($this->getRequest());
        return $match === $this->getRequest();
    }

    /**
     * @return bool
     * @throws InvalidState
     */
    protected function isComingFromDifferentPresenter(): bool {
        $match = $this->getRefererRequest();
        if (!$match instanceof Request) throw new InvalidState("Match not found");

        return $this->getRequest()->getPresenterName() !== $match->getPresenterName();
    }

    /**
     * @return bool
     * @throws InvalidState
     */
    protected function isComingFromDifferentModule(): bool {
        $match = $this->getRefererRequest();
        if (!$match instanceof Request) throw new InvalidState("Match not found");

        $module = substr($presenterName = $this->getRequest()->getPresenterName(), 0, strpos($presenterName, ":"));
        $refererModule = substr($refererPresenterName = $match->getPresenterName(), 0, strpos($refererPresenterName, ":"));
        return ($module !== $refererModule);
    }

    /**
     * @return Request|null
     * @throws InvalidState
     */
    protected function getRefererRequest(): ?Request {
        static $match = null;
        if ($match === null) {
            $referer = $this->getReferer();
            if (!$referer instanceof Url) throw new InvalidState("Referer not found");
            $script = $oldScript = new \Nette\Http\UrlScript($referer, "/");
            $request = new \Nette\Http\Request($script);
            $router = $this->getRouter();
            $match = $router->match($request) ?: false;
        }
        return $match instanceof Request ? $match : null;
    }

    protected function getReferer(): ?Url {
        $request = $this->getHttpRequest();
        return ($request instanceof Nette\Http\Request) ? $request->getReferer() : null;
    }

    private function getRouter(): IRouter {
        return $this->context->getByType(IRouter::class);
    }

    protected function getSliderManager(): ISliderManager {
        return $this->getServiceLoader()->getSliderManager();
    }

    protected function redrawContent() {
        $this->redrawControl("content");
    }

    protected function redrawHeader() {
        $this->redrawControl("header");
    }

    protected function getSignalName(): ?string {
        return $this->getParameter(self::SIGNAL_KEY);
    }

    /**
     * @param callable $action
     * @param callable|null $onException
     * @return mixed
     * @throws Exception
     */
    public function commonTryCall(callable $action, ?callable $onException = null) {
        try {
            return $action();
        } catch (Exception $exception) {
            \Tracy\Debugger::log($exception);
            $this->somethingWentWrong();

            if ($onException) $onException($exception);
            /*if ($exception)*/
            throw $exception;
        }
    }

    public function redrawFlashes() {
        $this->redrawControl("flashes");
    }

    public function handleClearFlashes() {
        $this->redrawFlashes();
    }
}