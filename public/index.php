<?php
require __DIR__ . "/../vendor/autoload.php";

function printcaller() {
    $trace = debug_backtrace();
    if (isset($trace[2]["class"]) && isset($trace[1]["line"]) && isset($trace[2]["function"])) {
        $trace = ["Called at:" =>
                      [
                          "class"  => $trace[2]["class"],
                          "line"   => $trace[1]["line"],
                          "method" => $trace[2]["function"],
                      ],
        ];
        dump($trace);
    } else {
        dump($trace[2]);
    }
}

function dump() {
    foreach (func_get_args() as $arg) {
        //\Tracy\Debugger::dump($arg);
        Tracy\Debugger::barDump($arg);
    }
    //printcaller();
}

//developer function
function diedump($var) {
    foreach (func_get_args() as $arg) {
        Tracy\Debugger::dump($arg);
        dump($arg);
    }
    printcaller();
    throw new Exception("diedumped");
}

function dumpException() {
    try {
        throw new \Exception();
    } catch (\Exception $exception) {
        Tracy\Debugger::log($exception);
    }
}

set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline, array $errcontext) {
    throw new ErrorException($errstr, $errno, 1, $errfile, $errline, null);
});

//\Tracy\OutputDebugger::enable();

require __DIR__ . "/../app/bootstrap.php";