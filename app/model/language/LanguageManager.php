<?php


use Nette\Caching\IStorage;
use Nette\Database\Context;
use Nette\Database\Table\ActiveRow;
use Nette\Security\User;

class LanguageManager {

    const TABLE = "language",
        COLUMN_ID = "language_id",
        COLUMN_CODE = "code", COLUMN_CODE_LENGTH = 5,

        SETTINGS_DEFAULT_LANGUAGE = "language.default";

    /**
     * @var  Context
     */
    private $database;
    /**
     * @var SettingsManager
     */
    private $settingsManager;
    /**
     * @var User
     */
    private $user;
    /**
     * @var \Nette\Caching\Cache
     */
    private $codeCache;
    /**
     * @var \Nette\Caching\Cache
     */
    private $idCache;
    /**
     * @var \Nette\DI\Container
     */
    private $context;

    /**
     * LanguageManager constructor.
     * @param IStorage $storage
     * @param \Nette\DI\Container $context
     */
    public function __construct(IStorage $storage, \Nette\DI\Container $context) {
        $cache = new \Nette\Caching\Cache($storage, "language");
        $this->codeCache = $cache->derive("code");
        $this->idCache = $cache->derive("id");
        $this->context = $context;
    }

    /**
     * @param bool $asObjects
     * @return string[]|Language[]
     */
    public function getAvailableLanguages($asObjects = false): array {
        $data = $this->getDatabase()->table(self::TABLE)->fetchAll();
        $langs = [];
        /** @var ActiveRow $lang */
        foreach ($data as $lang) {
            $langs[] = ($asObjects) ? $this->createFromRow($lang) : $lang[self::COLUMN_CODE];
        }
        return $langs;
    }

    /**
     * @return Language
     * @throws Exception
     */
    public function getDefaultLanguage(): Language {
        $defaultLang = $this->getSettingsManager()->get(self::SETTINGS_DEFAULT_LANGUAGE);

        if (!$defaultLang instanceof Setting || !($language = $this->getById($defaultLang->getValue()))) {
            throw new Exception("DefaultLang not set or doesnt exist");
        }

        return $language;
    }

    public function exists(string $langCode): bool {
        return $this->getByCode($langCode) instanceof Language;
    }

    public function getByCode(string $langCode): ?Language {
        return $this->codeCache->load($langCode);
    }

    private function createFromRow(ActiveRow $data): Language {
        return new Language(
            $data[self::COLUMN_ID],
            $data[self::COLUMN_CODE]
        );
    }

    public function getById(int $id):?Language {
        return $this->idCache->load($id);
    }

    protected function getBy(array $where):?Language {
        $data = $this->getDatabase()->table(self::TABLE)->where($where)->fetch();

        if ($data instanceof Nette\Database\Table\ActiveRow) {
            return $this->createFromRow($data);
        }
        return null;
    }

    /**
     * @return Context
     */
    private function getDatabase(): Context {
        if (!$this->database instanceof Context) {
            $this->database = $this->context->getByType(Context::class);
        }
        return $this->database;
    }

    /**
     * @return User
     */
    private function getUser(): User {
        if (!$this->user instanceof User) {
            $this->user = $this->context->getByType(User::class);
        }
        return $this->user;
    }

    /**
     * @return SettingsManager
     */
    private function getSettingsManager(): SettingsManager {
        if (!$this->settingsManager instanceof SettingsManager) {
            $this->settingsManager = $this->context->getByType(SettingsManager::class);
        }
        return $this->settingsManager;
    }

    public function rebuildCache() {
        foreach ($this->getAvailableLanguages(true) as $language) {
            $this->idCache->save($language->getId(), $language);
            $this->codeCache->save($language->getCode(), $language);
        }
    }
}