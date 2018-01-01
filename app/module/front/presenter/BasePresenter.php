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

    public function checkRequirements($element) {
        $this->checkCurrentIdentity();
        $this->checkRoles();
        parent::checkRequirements($element);
    }

    public function startup() {
        $this->invalidLinkMode = self::INVALID_LINK_EXCEPTION;
        $this->checkRefererAndDisallowAjax();
        $this->setDefaultSnippets(["content"]);
        parent::startup();
    }

    protected function checkPaging(int $currentPage, int $maxPage, string $page_key) {
        if ($currentPage < 1) $this->redirect(302, "this", [$page_key => 1]);
        else if ($currentPage > $maxPage) $this->redirect(302, "this", [$page_key => $maxPage]);
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
                dump("Identity not found");
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

    protected function getLocaleLanguage(): Language {
        if (!$this->localeLang instanceof Language)
            $this->localeLang = $this->getLanguageManager()->getByCode($this->translator->getLocale());
        if (!$this->localeLang instanceof Language)
            $this->localeLang = $this->getLanguageManager()->getDefaultLanguage();
        return $this->localeLang;
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

    protected final function getUserManager(): IUserManager {
        return $this->getServiceLoader()->getUserManager();
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

    protected final function getMediaManager(): IMediaManager {
        return $this->getServiceLoader()->getMediaManager();
    }

    protected final function getFormFactory(): FormFactory {
        return $this->context->getByType(FormFactory::class);
    }


    private function checkRefererAndDisallowAjax() {
        if ($this->isAjax() && $referer = $this->getReferer()) {
            $script = ($oldScript = new \Nette\Http\UrlScript($referer, "/"));
            $request = new \Nette\Http\Request($script);
            $router = $this->getRouter();
            $match = $router->match($request);
            if ($match instanceof Request) {
                $module = substr($presenterName = $this->getRequest()->getPresenterName(), 0, strpos($presenterName, ":"));
                $refererModule = substr($refererPresenterName = $match->getPresenterName(), 0, strpos($refererPresenterName, ":"));

                if ($module !== $refererModule) {
                    trigger_error("MODULES are not the same $presenterName and $refererPresenterName");
                    $this->disallowAjax();
                }
            }
            //dump("start", $this->getHttpRequest(), $referer, $oldScript, $script, $request, $script->getBasePath(), $match, "end");
        }
    }

    protected function getReferer():?Url {
        $request = $this->getHttpRequest();
        return ($request instanceof Nette\Http\Request) ? $request->getReferer() : null;
    }

    private function getRouter(): IRouter {
        return $this->context->getByType(IRouter::class);
    }

    protected function getSignalName():?string {
        return $this->getParameter(self::SIGNAL_KEY);
    }

    public function commonTryCall(callable $action, ?callable $onException = null) {
        try {
            return $action();
        } catch (Exception $exception) {
            if ($onException) $onException($exception);
            /*\Tracy\Debugger::log($exception);
            $this->somethingWentWrong();*/
            throw $exception;
        }
    }
}