<?php


use Kdyby\Translation\Translator;
use Nette\Caching\IStorage;
use Nette\Database\Context;
use Nette\DI\Container;
use Nette\Security\User;

abstract class Manager implements IManager {

    private static $initingClass = null;

    /** @var  Container */
    private $context;

    /** @var callable[] */
    private static $on = [];

    public final function __construct(Container $container) {
        $this->context = $container;
        $className = get_class($this);
        dump("initing " . $className);
        if (!self::$initingClass) {
            self::$initingClass = $className;
            dump("initing serviceloader");
            $this->getServiceLoader();
        }
        $this->init();
    }

    public static function getInitingClass(): ?string {
        return self::$initingClass;
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

    protected final function getDefaultStorage(): IStorage {
        return $this->context->getByType(IStorage::class);
    }

    protected final function getPageManager(): IPageManager {
        return $this->getServiceLoader()->getPageManager();
    }

    protected final function getLanguageManager(): ILanguageManager {
        return $this->getServiceLoader()->getLanguageManager();
    }

    protected final function getSettingsManager(): ISettingsManager {
        return $this->getServiceLoader()->getSettingsManager();
    }

    protected final function getUser(): User {
        return $this->getServiceLoader()->getUser();
    }

    protected final function getTranslator(): Translator {
        return $this->getServiceLoader()->getTranslator();
    }

    protected final function getTagManager(): ITagManager {
        return $this->getServiceLoader()->getTagManager();
    }

    protected final function getMediaManager(): IMediaManager {
        return $this->getServiceLoader()->getMediaManager();
    }

    protected final function getDatabase(): Context {
        return $this->getServiceLoader()->getDatabase();
    }

    protected final function getUserManager(): IUserManager {
        return $this->getServiceLoader()->getUserManager();
    }

    protected final function getHeaderManager(): IHeaderManager {
        return $this->getServiceLoader()->getHeaderManager();
    }

    private function getServiceLoader(): ServiceLoader {
        return $this->context->getByType(ServiceLoader::class);
    }

    protected final function runInTransaction(callable $action, ?callable $onException = null) {
        $inTransaction = $this->getDatabase()->getConnection()->getPdo()->inTransaction();
        if (!$inTransaction) $this->getDatabase()->beginTransaction();
        try {

            $result = $action();

            if (!$inTransaction) $this->getDatabase()->commit();
        } catch (Throwable $exception) {
            if (!$inTransaction) $this->getDatabase()->rollBack();

            if ($onException) $onException($exception);

            throw $exception;
        }
        return $result;
    }
}