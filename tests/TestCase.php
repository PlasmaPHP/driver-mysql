<?php
/**
 * Plasma Core component
 * Copyright 2018 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/core/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL\Tests;

class TestCase extends \PHPUnit\Framework\TestCase {
    /**
     * @var \React\EventLoop\LoopInterface
     */
    public $loop;
    
    function setUp() {
        $this->loop = \React\EventLoop\Factory::create();
    }
    
    function await(\React\Promise\PromiseInterface $promise, float $timeout = 30.0) {
        return \Clue\React\Block\await($promise, $this->loop, $timeout);
    }
}
