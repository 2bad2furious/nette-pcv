<?php


use adminModule\PagePresenter;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;

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
        $router = new RouteList();

        /*if($this->consoleMode){
            $router[] = new Route("Console:");
            return $router;
        }*/
        $languages = implode("|", $this->languageManager->getAvailableLanguages());

        $router[] = $adminRouteList = new RouteList("admin");
        $router[] = $frontRouteList = new RouteList("front");
        //the redirect should be done here
        $frontRouteList[] = new Route("", [
            "presenter" => "NoLang",
        ]);
        $frontRouteList[] = new Route("test", "Test:default");

        $frontRouteList[] = new Route("<locale {$languages}>/" . PageManager::PAGE_URL_PERMANENT . "/<" . \frontModule\PagePresenter::PARAM_ID . " [0-9]+>", "Page:Permanent");
        $frontRouteList[] = new Route("<locale {$languages}>/[<" . \frontModule\PagePresenter::PARAM_URL . " " . PageManager::LOCAL_URL_CHARSET . ">]", "Page:default");

//        $frontRouteList[] = new Route("<presenter>/<action>");

        $availableAdminLangs = implode("|", ["en_US"]);

        $adminRouteList[] = new Route("admin/<locale>/<presenter page>/<action show>/<" . PagePresenter::TYPE_KEY . ">/<" . PagePresenter::VISIBILITY_KEY . ">/<" . PagePresenter::LANGUAGE_KEY . ">/<" . PagePresenter::HAS_TRANSLATION_KEY . "> ? <" . PagePresenter::PAGE_KEY . ">", [
            "locale"                           => [
                Route::PATTERN => $availableAdminLangs,
            ],
            PagePresenter::TYPE_KEY            => [
                Route::VALUE   => PagePresenter::DEFAULT_TYPE,
                Route::PATTERN => implode("|", PagePresenter::TYPES),
            ],
            PagePresenter::VISIBILITY_KEY      => [
                Route::VALUE   => PagePresenter::DEFAULT_VISIBILITY,
                Route::PATTERN => implode("|", PagePresenter::VISIBILITIES),
            ],
            PagePresenter::LANGUAGE_KEY        => [
                Route::VALUE   => PagePresenter::DEFAULT_LANGUAGE,
                Route::PATTERN => $languages,
            ],
            PagePresenter::PAGE_KEY            => [
                Route::VALUE   => 1,
                Route::PATTERN => "\d",
            ],
            PagePresenter::HAS_TRANSLATION_KEY => [
                Route::VALUE   => null,
                Route::PATTERN => "1|0",
            ],
        ]);

        $adminRouteList[] = new Route("admin/<locale $availableAdminLangs>/<presenter page>/<action create>/<" . PagePresenter::TYPE_KEY . ">", [
            PagePresenter::TYPE_KEY => [
                Route::PATTERN => implode("|", [PagePresenter::TYPE_POST, PagePresenter::TYPE_PAGE]),
            ],
        ]);

        $adminRouteList[] = new Route("admin/<locale $availableAdminLangs>/<presenter page>/<action edit>/<" . PagePresenter::ID_KEY . ">/<" . PagePresenter::LANGUAGE_KEY . ">", [
            PagePresenter::ID_KEY       => [
                Route::PATTERN => "\d+",
            ],
            PagePresenter::LANGUAGE_KEY => [
                Route::PATTERN => $languages,
            ],
        ]);

        $adminRouteList[] = new Route("admin/<locale $availableAdminLangs>/<presenter availability>/<" . \adminModule\AvailabilityPresenter::KEY . ">/<" . \adminModule\AvailabilityPresenter::LANGUAGE . "> ? <" . \adminModule\AvailabilityPresenter::VALUE . "><" . \adminModule\AvailabilityPresenter::ID . ">", [
            \adminModule\AvailabilityPresenter::KEY      => [
                Route::PATTERN => implode("|", \adminModule\AvailabilityPresenter::KEYS),
            ],
            \adminModule\AvailabilityPresenter::LANGUAGE => [
                Route::PATTERN => $languages,
            ],
            \adminModule\AvailabilityPresenter::ID       => [
                Route::OPTIONAL => 1,
                Route::PATTERN  => "\d+",
            ],
            "action"                                     => [
                Route::VALUE => "default",
            ],
        ]);

        $adminRouteList[] = new Route("admin/<locale $availableAdminLangs>/<presenter page>/<action delete>/<" . PagePresenter::ID_KEY . ">", [
            PagePresenter::ID_KEY => [
                Route::PATTERN => "\d+",
            ]]);

        $adminRouteList[] = new Route("admin", "Default:");

        $adminRouteList[] = new Route("admin/<locale $availableAdminLangs>/<presenter=Default>[/<action=default default>]");

        return $router;
    }
}