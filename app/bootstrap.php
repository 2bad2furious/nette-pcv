<?php
$configurator = new Nette\Configurator;

//$configurator->setDebugMode("localhost");

/*$configurator->setDebugMode([
    "localhost",
    "127.0.0.1",
    "192.168.1.54",
    "192.168.1.59",
    "192.168.6.7"
]);*/
//$configurator->setDebugMode(false);
\Tracy\Debugger::$maxDepth = 5;

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

$container = $configurator->createContainer();

try {
    /** @var \Nette\Application\Application $app */
    $app = $container->getByType(Nette\Application\Application::class);
    $app->run();
} catch (Throwable $ex) {
    throw $ex;
}