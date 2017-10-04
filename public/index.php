<?php

require __DIR__ . "/../vendor/autoload.php";

function printcaller() {
    try {
        throw new Exception();
    } catch (Exception $exception) {
        if (isset($exception->getTrace()[2]["class"]) && isset($exception->getTrace()[1]["line"]) && isset($exception->getTrace()[2]["function"])) {
            \Tracy\Debugger::dump(["Called at:" =>
                                       [
                                           "class"  => $exception->getTrace()[2]["class"],
                                           "line"   => $exception->getTrace()[1]["line"],
                                           "method" => $exception->getTrace()[2]["function"],
                                       ],
                ]
            );
        } else {
            \Tracy\Debugger::dump($exception->getTrace()[2]);
            Tracy\Debugger::barDump($exception->getTrace()[2]);
        }
    }
}

function dump() {
    foreach (func_get_args() as $arg) {
        \Tracy\Debugger::dump($arg);
        Tracy\Debugger::barDump($arg);
    }
    //printcaller();
}

//developer function
function diedump($var) {
    foreach (func_get_args() as $arg) {
        dump($arg);
    }
    printcaller();
    exit();
}

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline, array $errcontext) {
    throw new ErrorException($errstr, $errno, 1, $errfile, $errline, null);
});

//\Tracy\OutputDebugger::enable();

require __DIR__ . "/../app/bootstrap.php";