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

$connection = $container->getByType(\Nette\Database\Context::class);
$connection->beginTransaction();
try {
    $app = $container->getByType(Nette\Application\Application::class);
    $app->run();
    $connection->commit();
} catch (Exception $ex) {
    $connection->rollBack();
    throw $ex;
}