<?php


use Nette\Caching\IStorage;
use Nette\Database\Context;
use Nette\DI\Container;
use Nette\Security\User;

class ServiceLoader {
    private const CLASS_NAMES = [ITagManager::class, IPageManager::class, ILanguageManager::class, IAccountManager::class, ISettingsManager::class, IHeaderManager::class, IFileManager::class];
    /** @var  Container */
    private $context;


    /**
     * ServiceLoader constructor.
     * @param Container $context
     */
    public function __construct(Container $context) {
        $this->context = $context;
        /*
                //INITING for listener-registration
                $reflection = $this->getReflection();
                //dump(Manager::getInitingClass());
                foreach (self::CLASS_NAMES as $k => $v) {
                dump($context->findByType($v));
                    if (!$reflection instanceof ReflectionClass) $reflection = $this->getReflection();

                    dump($v, $reflection, ($reflection instanceof ReflectionClass && $reflection->implementsInterface($v)));
                    if (!$reflection instanceof ReflectionClass || !$reflection->implementsInterface($v))
                        $this->context->getByType($v);
                }*/
    }

    private function getReflection(): ?ReflectionClass {
        $managerInitingClass = Manager::getInitingClass();
        return $managerInitingClass ? new ReflectionClass($managerInitingClass) : null;
    }

    public final function getDatabase(): Context {
        return $this->context->getByType(Context::class);
    }

    public final function getUser(): User {
        return $this->context->getByType(User::class);
    }


    public final function getAccountManager(): IAccountManager {
        return $this->context->getByType(IAccountManager::class);
    }


    public final function getLanguageManager(): ILanguageManager {
        return $this->context->getByType(ILanguageManager::class);
    }

    public final function getHeaderManager(): IHeaderManager {
        return $this->context->getByType(IHeaderManager::class);
    }

    public final function getTagManager(): ITagManager {
        return $this->context->getByType(ITagManager::class);
    }

    public final function getPageManager(): IPageManager {
        return $this->context->getByType(IPageManager::class);
    }

    public final function getSettingsManager(): ISettingsManager {
        return $this->context->getByType(ISettingsManager::class);
    }

    public final function getTranslator(): \Kdyby\Translation\Translator {
        return $this->context->getByType(\Kdyby\Translation\Translator::class);
    }

    public final function getFileManager(): IFileManager {
        return $this->context->getByType(IFileManager::class);
    }

    public final function getSliderManager(): ISliderManager {
        return $this->context->getByType(ISliderManager::class);
    }

    public final function getContext(): Container {
        return $this->context;
    }
}