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

        if (!self::$initingClass) {
            self::$initingClass = $className;
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
                try {
                    call_user_func($listener, $arg);
                } catch (Exception $x) {
                    \Tracy\Debugger::log($x);
                }
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

    protected final function getMediaManager(): IFileManager {
        return $this->getServiceLoader()->getFileManager();
    }

    protected final function getDatabase(): Context {
        return $this->getServiceLoader()->getDatabase();
    }

    protected final function getAccountManager(): IAccountManager {
        return $this->getServiceLoader()->getAccountManager();
    }

    protected final function getHeaderManager(): IHeaderManager {
        return $this->getServiceLoader()->getHeaderManager();
    }

    private function getServiceLoader(): ServiceLoader {
        return $this->context->getByType(ServiceLoader::class);
    }

    protected function getSliderManager(): ISliderManager {
        return $this->getServiceLoader()->getSliderManager();
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