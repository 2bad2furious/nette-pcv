<?php


use Nette\Application\Routers\Route;

class Router {
    /** @var bool */
    private $consoleMode;
    /**
     * @var LanguageManager
     */
    private $languageManager;

    /**
     * Router constructor.
     * @param bool $consoleMode
     * @param LanguageManager $languageManager
     */
    public function __construct(bool $consoleMode, LanguageManager $languageManager) {
        $this->consoleMode = $consoleMode;
        $this->languageManager = $languageManager;
    }

    public function createRouter(): \Nette\Application\IRouter {
        $router = new \Nette\Application\Routers\RouteList();

        $languages = implode("|", $this->languageManager->getAvailableLanguages());

        //the redirect should be done here
        $router[] = new Route("", [
            "presenter" => "NoLang",
            "module"    => "front",
        ]);
        $router[] = new Route("test", [
            "module"    => "front",
            "presenter" => "Test",
            "action"    => "Default",
        ]);
        $router[] = new Route("admin/<locale $languages>/<presenter>[/<action>]", [
            "module"    => "admin",
            "presenter" => "Default",
            "action"    => "Default",
        ]);
        $router[] = new Route("<locale {$languages}>/permanent/<page_id [0-9]+>", [
            "module"    => "front",
            "presenter" => "Page",
            "action"    => "Permanent",
        ]);
        $router[] = new Route("<locale {$languages}>/[<url " . PageManager::LOCAL_URL_CHARSET . ">]", [
            "module"    => "front",
            "presenter" => "Page",
            "action"    => "Default",
        ]);
        return $router;
    }
}