<?php
require_once __DIR__ . "/../vendor/autoload.php";

Tester\Environment::setup();

$configurator = new Nette\Configurator;
$configurator->setTempDirectory(__DIR__.'/tmp');

// RobotLoader => autoloading
$loader = $configurator->createRobotLoader();
$loader->addDirectory(__DIR__ . "/../app");
$loader->addDirectory(__DIR__);
$loader->register();

// Config files
$configurator->addConfig(__DIR__ . "/../config/config.neon");
$configurator->addConfig(__DIR__ . "/config.tests.neon");

return $configurator->createContainer();
