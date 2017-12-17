<?php


use Nette\Caching\IStorage;
use Nette\Database\Context;
use Nette\DI\Container;
use Nette\Security\User;

class ServiceLoader {
//TODO dont always call DIC
    private const CLASS_NAMES = [TagManager::class, PageManager::class, LanguageManager::class, UserManager::class, SettingsManager::class, HeaderManager::class, MediaManager::class];

    /** @var  Container */
    private $context;


    /**
     * ServiceLoader constructor.
     * @param Container $context
     */
    public function __construct(Container $context) {
        $this->context = $context;

        //INITING for listener-registration, TODO better - maybe do static listener registration
        foreach (self::CLASS_NAMES as $k => $v) {
            if (Manager::getInitingClass() !== $v)
                $this->context->getByType($v);
        }
    }

    public final function getDatabase(): Context {
        return $this->context->getByType(Context::class);
    }

    public final function getUser(): User {
        return $this->context->getByType(User::class);
    }


    public final function getUserManager(): UserManager {
        return $this->context->getByType(UserManager::class);
    }


    public final function getLanguageManager(): LanguageManager {
        return $this->context->getByType(LanguageManager::class);
    }

    public final function getHeaderManager(): HeaderManager {
        return $this->context->getByType(HeaderManager::class);
    }

    public final function getTagManager(): TagManager {
        return $this->context->getByType(TagManager::class);
    }

    public final function getPageManager(): PageManager {
        return $this->context->getByType(PageManager::class);
    }

    public final function getSettingsManager(): SettingsManager {
        return $this->context->getByType(SettingsManager::class);
    }

    public final function getTranslator(): \Kdyby\Translation\Translator {
        return $this->context->getByType(\Kdyby\Translation\Translator::class);
    }

    public final function getMediaManager(): MediaManager {
        return $this->context->getByType(MediaManager::class);
    }

    public final function getContext(): Container {
        return $this->context;
    }
}