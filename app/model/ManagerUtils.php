<?php


use Kdyby\Translation\Translator;
use Nette\Caching\IStorage;
use Nette\Database\Context;
use Nette\DI\Container;
use Nette\Security\User;

trait ManagerUtils {

    private static $initingClasses = null;

    /** @var  Container */
    private $context;

    /** @var callable[] */
    private static $on = [];

    public final function __construct(Container $container) {
        $this->context = $container;
        $className = get_class($this);
        dump($className, self::$initingClasses);
        if (!self::$initingClasses) {
            self::$initingClasses = $className;
            $this->getServiceLoader();
        }
        $this->init();
    }

    public static function getInitingClass():?string {
        return self::$initingClasses;
    }

    protected abstract function init();

    private final function getContext(): Container {
        return $this->context;
    }

    public static function on(string $trigger, callable $callback) {
        if (!isset(self::$on[$trigger])) self::$on[$trigger] = [$callback];
        else self::$on[$trigger][] = $callback;
    }

    private function trigger(string $trigger, $arg) {
        if (isset(self::$on[$trigger]))
            foreach (self::$on[$trigger] as $listener) {
                call_user_func($listener, $arg);
            }
    }

    private function throwIfNoRights(string $action) {
        if (!$this->getUser()->isAllowed($action) && !defined("FULL_RIGHTS")) throw new Exception("Not allowed.");
    }

    private final function getDefaultStorage(): IStorage {
        return $this->context->getByType(IStorage::class);
    }

    private final function getPageManager(): PageManager {
        return $this->getServiceLoader()->getPageManager();
    }

    private final function getLanguageManager(): LanguageManager {
        return $this->getServiceLoader()->getLanguageManager();
    }

    private final function getSettingsManager(): SettingsManager {
        return $this->getServiceLoader()->getSettingsManager();
    }

    private final function getUser(): User {
        return $this->getServiceLoader()->getUser();
    }

    private final function getTranslator(): Translator {
        return $this->getServiceLoader()->getTranslator();
    }

    private final function getTagManager(): TagManagerUtils {
        return $this->getServiceLoader()->getTagManager();
    }

    private final function getMediaManager(): MediaManager {
        return $this->getServiceLoader()->getMediaManager();
    }

    private final function getDatabase(): Context {
        return $this->getServiceLoader()->getDatabase();
    }

    private final function getUserManager(): UserManager {
        return $this->getServiceLoader()->getUserManager();
    }

    private final function getHeaderManager(): HeaderManager {
        return $this->getServiceLoader()->getHeaderManager();
    }

    private function getServiceLoader(): ServiceLoader {
        return $this->context->getByType(ServiceLoader::class);
    }
}