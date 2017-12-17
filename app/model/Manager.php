<?php


use Kdyby\Translation\Translator;
use Nette\Caching\IStorage;
use Nette\Database\Context;
use Nette\DI\Container;
use Nette\Security\User;

abstract class Manager {

    private static $initingClasses = null;

    /** @var  Container */
    private $context;

    /** @var callable[] */
    private static $on = [];

    public final function __construct(Container $container) {
        $this->context = $container;
        $className = get_class($this);
        if (!self::$initingClasses) {
            self::$initingClasses = $className;
            $this->getServiceLoader();
        }
        $this->init();
    }

    public static function getInitingClass():?string{
        return self::$initingClasses;
    }

    protected function init() {
    }

    protected final function getContext(): Container {
        return $this->context;
    }

    public static function on(string $trigger, callable $callback) {
        if (!isset(self::$on[$trigger])) self::$on[$trigger] = [$callback];
        else self::$on[$trigger][] = $callback;
    }

    protected function trigger(string $trigger, $arg) {
        if (isset(self::$on[$trigger]))
            foreach (self::$on[$trigger] as $listener) {
                call_user_func($listener, $arg);
            }
    }

    protected function throwIfNoRights(string $action) {
        if (!$this->getUser()->isAllowed($action) && !defined("FULL_RIGHTS")) throw new Exception("Not allowed.");
    }

    protected final function getDefaultStorage(): IStorage {
        return $this->context->getByType(IStorage::class);
    }

    protected final function getPageManager(): PageManager {
        return $this->getServiceLoader()->getPageManager();
    }

    protected final function getLanguageManager(): LanguageManager {
        return $this->getServiceLoader()->getLanguageManager();
    }

    protected final function getSettingsManager(): SettingsManager {
        return $this->getServiceLoader()->getSettingsManager();
    }

    protected final function getUser(): User {
        return $this->getServiceLoader()->getUser();
    }

    protected final function getTranslator(): Translator {
        return $this->getServiceLoader()->getTranslator();
    }

    protected final function getTagManager(): TagManager {
        return $this->getServiceLoader()->getTagManager();
    }

    protected final function getMediaManager(): MediaManager {
        return $this->getServiceLoader()->getMediaManager();
    }

    protected final function getDatabase(): Context {
        return $this->getServiceLoader()->getDatabase();
    }

    protected final function getUserManager(): UserManager {
        return $this->getServiceLoader()->getUserManager();
    }

    protected final function getHeaderManager(): HeaderManager {
        return $this->getServiceLoader()->getHeaderManager();
    }

    private function getServiceLoader(): ServiceLoader {
        return $this->context->getByType(ServiceLoader::class);
    }
}