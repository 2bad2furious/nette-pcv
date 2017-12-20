<?php
$configurator = new Nette\Configurator;

//$configurator->setDebugMode("localhost");

$configurator->setDebugMode([
    "localhost",
    "127.0.0.1",
    "192.168.1.54",
    "192.168.1.59",
]);
//$configurator->setDebugMode(false);


// Enable Tracy for error visualisation & logging
$configurator->enableTracy(__DIR__ . '/../log');

// Sets cache directory
$configurator->setTempDirectory(__DIR__ . '/../temp');

// RobotLoader => autoloading
$loader = $configurator->createRobotLoader();
$loader->addDirectory(__DIR__ . "/../app");
$loader->register();

// Config files
$configurator->addConfig(__DIR__ . "/../config/config.neon");
$configurator->addConfig(__DIR__ . "/../config/config.local.neon");
$configurator->addConfig(__DIR__ . "/../config/config.product.neon");

$container = $configurator->createContainer();

//Dont do this until you can rollback cache as well
/** @var \Nette\Database\Context $connection */
$connection = $container->getByType(\Nette\Database\Context::class);
$connection->beginTransaction();

try {
    /** @var \Nette\Application\Application $app */
    $app = $container->getByType(Nette\Application\Application::class);
    $app->run();
    $connection->commit();
} catch (Throwable $ex) {
    $hasAnythingBeenChanged = $connection->getInsertId();
    $connection->rollBack();
    //dump($hasAnythingBeenChanged,(bool)$hasAnythingBeenChanged);
    if ($hasAnythingBeenChanged) {
        define("FULL_RIGHTS", true);
        dump("shitty solution", $ex);
        //shitty solution incoming
        $rebuildableManagers = [];
        $rebuildableManagers[] = $container->getByType(SettingsManager::class);
        $rebuildableManagers[] = $container->getByType(LanguageManager::class);
        $rebuildableManagers[] = $container->getByType(PageManager::class);
        $rebuildableManagers[] = $container->getByType(HeaderManager::class);
        $rebuildableManagers[] = $container->getByType(UserManager::class);
        foreach ($rebuildableManagers as $rebuildableManager) {
            $rebuildableManager->rebuildCache();
        }
        //shitty solution ended xd
    }
    throw $ex;
}