<?php


use Nette\Caching\IStorage;
use Nette\Database\Context;
use Nette\DI\Container;
use Nette\Security\User;

class ServiceLoader {
    private const CLASS_NAMES = [ITagManager::class, IPageManager::class, ILanguageManager::class, IUserManager::class, ISettingsManager::class, IHeaderManager::class, IMediaManager::class];

    /** @var  Container */
    private $context;


    /**
     * ServiceLoader constructor.
     * @param Container $context
     */
    public function __construct(Container $context) {
        $this->context = $context;

        //INITING for listener-registration, TODO better - maybe do static listener registration
        $reflection = $this->getReflection();
        //dump(Manager::getInitingClass());
        foreach (self::CLASS_NAMES as $k => $v) {
            if (!$reflection instanceof ReflectionClass) $reflection = $this->getReflection();

            if (!$reflection instanceof ReflectionClass || !$reflection->implementsInterface($v))
                $this->context->getByType($v);
        }
    }

    private function getReflection():?ReflectionClass {
        $managerInitingClass = Manager::getInitingClass();
        return $managerInitingClass ? new ReflectionClass($managerInitingClass) : null;
    }

    public final function getDatabase(): Context {
        return $this->context->getByType(Context::class);
    }

    public final function getUser(): User {
        return $this->context->getByType(User::class);
    }


    public final function getUserManager(): IUserManager {
        return $this->context->getByType(IUserManager::class);
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

    public final function getMediaManager(): IMediaManager {
        return $this->context->getByType(IMediaManager::class);
    }

    public final function getContext(): Container {
        return $this->context;
    }
}