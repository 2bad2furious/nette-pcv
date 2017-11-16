<?php


use Nette\Caching\IStorage;
use Nette\Database\Context;
use Nette\DI\Container;
use Nette\Security\User;

class ServiceLoader {

    /** @var  LanguageManager */
    private $languageManager;

    /** @var  PageManager */
    private $pageManager;

    /** @var  SettingsManager */
    private $settingsManager;

    /** @var  HeaderManager */
    private $headerManager;

    /** @var  Nette\Database\Context */
    private $database;

    /** @var  Container */
    private $context;

    /** @var  User */
    private $user;

    /** @var  UserManager */
    private $userManager;

    /** @var  TagManager */
    private $tagManager;

    /** @var  IStorage */
    private $storage;

    /** @var  \Kdyby\Translation\Translator */
    private $translator;

    /** @var MediaManager */
    private $mediaManager;

    /**
     * ServiceLoader constructor.
     * @param Container $context
     */
    public function __construct(Container $context) {
        $this->context = $context;
    }


    public final function getIStorage():IStorage{
        if (!$this->storage instanceof IStorage) {
            $this->storage = $this->context->getByType(IStorage::class);
        }
        return $this->storage;
    }

    public final function getDatabase(): Context {
        if (!$this->database instanceof Context) {
            $this->database = $this->context->getByType(Context::class);
        }
        return $this->database;
    }

    public final function getUser(): User {
        if (!$this->user instanceof User) {
            $this->user = $this->context->getByType(User::class);
        }
        return $this->user;
    }


    public final function getUserManager(): UserManager {
        if (!$this->userManager instanceof UserManager) {
            $this->userManager = $this->context->getByType(UserManager::class);
        }
        return $this->userManager;
    }


    public final function getLanguageManager(): LanguageManager {
        if (!$this->languageManager instanceof LanguageManager) {
            $this->languageManager = $this->context->getByType(LanguageManager::class);
        }
        return $this->languageManager;
    }

    public final function getHeaderManager(): HeaderManager {
        if (!$this->headerManager instanceof HeaderManager) {
            $this->headerManager = $this->context->getByType(HeaderManager::class);
        }
        return $this->headerManager;
    }

    public final function getTagManager(): TagManager {
        if (!$this->tagManager instanceof TagManager) {
            $this->tagManager = $this->context->getByType(TagManager::class);
        }
        return $this->tagManager;
    }

    public final function getPageManager(): PageManager {
        if (!$this->pageManager instanceof PageManager) {
            $this->pageManager = $this->context->getByType(PageManager::class);
        }
        return $this->pageManager;
    }

    public final function getSettingsManager(): SettingsManager {
        if (!$this->settingsManager instanceof SettingsManager) {
            $this->settingsManager = $this->context->getByType(SettingsManager::class);
        }
        return $this->settingsManager;
    }

    public final function getTranslator(): \Kdyby\Translation\Translator {
        if (!$this->translator instanceof \Kdyby\Translation\Translator) {
            $this->translator = $this->context->getByType(\Kdyby\Translation\Translator::class);
        }
        return $this->translator;
    }

    public final function getMediaManager(): MediaManager {
        if (!$this->mediaManager instanceof MediaManager) {
            $this->mediaManager = $this->context->getByType(MediaManager::class);
        }
        return $this->mediaManager;
    }

    public final function getContext(): Container {
        return $this->context;
    }
}