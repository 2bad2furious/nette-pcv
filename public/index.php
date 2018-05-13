<?php


require __DIR__ . "/../vendor/autoload.php";

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline, array $errcontext) {
    throw new ErrorException($errstr, $errno, 1, $errfile, $errline, null);
});
//\Tracy\OutputDebugger::enable();

require __DIR__ . "/../app/bootstrap.php";