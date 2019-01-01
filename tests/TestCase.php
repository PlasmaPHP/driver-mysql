<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
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
    
    function await(\React\Promise\PromiseInterface $promise, float $timeout = 10.0) {
        return \Clue\React\Block\await($promise, $this->loop, $timeout);
    }
}
