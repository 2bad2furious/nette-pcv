<?php


use Nette\Caching\Cache;
use Nette\Caching\IStorage;
use Nette\Database\Context;
use Nette\Database\Table\ActiveRow;

class HeaderManager {
    const TABLE = "header",
        COLUMN_ID = "header_id",
        COLUMN_LANG = "lang",
        COLUMN_PAGE_ID = "page_id",
        COLUMN_PAGE_URL = "url",
        COLUMN_TITLE = "title",
        COLUMN_POSITION = "position",
        COLUMN_PARENT_ID = "parent_id";

    /**
     * @var Context
     */
    private $database;

    /**
     * @var PageManager
     */
    private $pageManager;

    /**
     * @var Cache
     */
    private $cache;
    /**
     * @var LanguageManager
     */
    private $languageManager;
    /**
     * @var \Nette\DI\Container
     */
    private $context;

    /**
     * HeaderManager constructor.
     * @param IStorage $storage
     * @param \Nette\DI\Container $context
     */
    public function __construct(IStorage $storage, \Nette\DI\Container $context) {
        $this->cache = new Cache($storage, "header");
        $this->context = $context;
    }

    public function rebuildCache() {
        $languages = $this->getDatabase()->table(self::TABLE)->select(self::COLUMN_LANG)->group(self::COLUMN_LANG);
        /** @var ActiveRow $langRow */
        foreach ($languages as $langRow) {
            $root = new HeaderPage(0, 0, $language = $this->getLanguageManager()->getById($langRow[self::COLUMN_LANG]), false);
            $this->addChildren($root, $language);

            $this->cache->save($language->getId(), $root);
        }
    }

    /**
     * @param HeaderPage $header
     * @param Language $language
     * @throws Exception
     */
    private function addChildren(HeaderPage &$header, Language $language): void {
        $rows = $this->getDatabase()->table(self::TABLE)->where([
            self::COLUMN_LANG      => $language->getId(),
            self::COLUMN_PARENT_ID => $header->getHeaderPageId(),
        ])->fetchAll();

        /** @var ActiveRow $row */
        foreach ($rows as $row) {
            $headerPage = new HeaderPage(
                $row[self::COLUMN_ID],
                $row[self::COLUMN_PAGE_ID],
                $language,
                ($row[self::COLUMN_PAGE_ID] === null) ? $row[self::COLUMN_PAGE_URL] : null,
                ($row[self::COLUMN_PAGE_ID] === null) ? $row[self::COLUMN_TITLE] : null
            );

            $this->addChildren($headerPage, $language);
            $header->addChild($headerPage);
        }
    }

    /**
     * @param Language $language
     * @param null|Page $currentPage
     * @return HeaderPage|null
     */
    public function getRoot(Language $language, ?Page $currentPage): ?HeaderPage {
        /** @var HeaderPage|null $root */
        $root = $this->cache->load($language->getId());
        if ($root instanceof HeaderPage) {
            $this->setPagesOnChildren($root, $currentPage, $language);
        }
        return $root;
    }

    private function setPagesOnChildren(HeaderPage $parent, ?Page &$currentPage, Language $language) {
        foreach ($parent->getChildren() as $child) {
            $this->setPagesOnChildren($child, $currentPage, $language);
            $pageId = $child->getPageId();
            $page = (is_int($pageId)) ? $this->getPageManager()->getByGlobalId($language, $pageId) : null;
            if ($page instanceof Page) $child->setPage($page);
            $child->setActive($currentPage instanceof Page ? $child->getUrl() === $currentPage->getCompleteUrl() : false);
        }
    }

    public function isInHeader(int $globalId): bool {
        return boolval($this->getDatabase()->table(self::TABLE));
    }

    private function getDatabase(): Context {
        if (!$this->database instanceof Context) {
            $this->database = $this->context->getByType(Context::class);
        }
        return $this->database;
    }

    public function getPageManager(): PageManager {
        if (!$this->pageManager instanceof PageManager) {
            $this->pageManager = $this->context->getByType(PageManager::class);
        }
        return $this->pageManager;
    }

    private function getLanguageManager(): LanguageManager {
        if (!$this->languageManager instanceof LanguageManager) {
            $this->languageManager = $this->context->getByType(LanguageManager::class);
        }
        return $this->languageManager;
    }

}