<?php


use Kdyby\Translation\Translator;
use Nette\Caching\IStorage;
use Nette\Database\Context;
use Nette\DI\Container;
use Nette\Security\User;

abstract class Manager {

    /** @var ServiceLoader */
    private $serviceLoader;


    /** @var  Container */
    private $context;

    /** @var callable[] */
    private $on = [];

    public final function __construct(Container $container) {
        $this->context = $container;
        $this->serviceLoader = $container->getByType(ServiceLoader::class);
        $this->init();
    }

    protected function init() {
    }

    protected final function getContext(): Container {
        return $this->context;
    }

    protected function on(string $trigger, callable $callback) {
        if (!isset($this->on[$trigger])) $this->on[$trigger] = [$callback];
        else $this->on[$trigger][] = $callback;
    }

    public function trigger(string $trigger, $arg) {
        if (isset($this->on[$trigger]))
            foreach ($this->on[$trigger] as $listener) {
                call_user_func($listener, $arg);
            }
    }

    protected final function getDefaultStorage(): IStorage {
        return $this->serviceLoader->getIStorage();
    }

    protected final function getPageManager(): PageManager {
        return $this->serviceLoader->getPageManager();
    }

    protected final function getLanguageManager(): LanguageManager {
        return $this->serviceLoader->getLanguageManager();
    }

    protected final function getSettingsManager(): SettingsManager {
        return $this->serviceLoader->getSettingsManager();
    }

    protected final function getUser(): User {
        return $this->serviceLoader->getUser();
    }

    protected final function getTranslator(): Translator {
        return $this->serviceLoader->getTranslator();
    }

    protected final function getTagManager(): TagManager {
        return $this->serviceLoader->getTagManager();
    }

    protected final function getMediaManager(): MediaManager {
        return $this->serviceLoader->getMediaManager();
    }

    protected final function getDatabase(): Context {
        return $this->serviceLoader->getDatabase();
    }

    protected final function getUserManager(): UserManager {
        return $this->serviceLoader->getUserManager();
    }

    protected final function getHeaderManager(): HeaderManager {
        return $this->serviceLoader->getHeaderManager();
    }
}