<?php

$container = require __DIR__ . "/../bootstrap.php";

/**
 * @testCase
 */
class IPageManager_add extends BaseTest {

    /** @var IPageManager */
    private $pm;

    function setUp(){
        $this->pm = $this->getContext()->getByType(IPageManager::class);
    }

    function testType(){
        //$id = $this->pm->
    }

    function testDb() {

    }

    function testStatus() {

    }
}

(new IPageManager_add($container))->run();
