<?php


abstract class BaseControl extends \Nette\Application\UI\Control {
    /** @var  ServiceLoader */
    private $serviceLoader;

    public function __construct(BasePresenter $presenter, $name) {
        parent::__construct($presenter, $name);
        $this->initTranslator();
        $this->template->setFile(__DIR__ . "/templates/" . $this->getName() . ".latte");
    }

    protected function initTranslator() {
        $this->template->setTranslator($this->getPresenter()->translator);
    }

    protected final function getServiceLoader(): ServiceLoader {
        if (!$this->serviceLoader instanceof ServiceLoader)
            $this->serviceLoader = $this->getPresenter()->context->getByType(ServiceLoader::class);
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


}