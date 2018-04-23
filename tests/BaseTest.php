<?php


use Nette\DI\Container;

require_once __DIR__."/../vendor/nette/tester/src/Framework/TestCase.php";

abstract class BaseTest extends \Tester\TestCase {
    /**
     * @var Container
     */
    private $context;

    /**
     * BaseTest constructor.
     * @param Container $context
     */
    public function __construct(Container $context) {
        $this->context = $context;
    }

    /**
     * @return Container
     */
    protected function getContext(): Container {
        return $this->context;
    }
}