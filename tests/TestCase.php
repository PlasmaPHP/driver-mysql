<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
 * @noinspection PhpUnhandledExceptionInspection
*/

namespace Plasma\Drivers\MySQL\Tests;

use React\EventLoop\Factory;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;
use function Clue\React\Block\await;

class TestCase extends \PHPUnit\Framework\TestCase {
    /**
     * @var LoopInterface
     */
    public $loop;
    
    function setUp() {
        $this->loop = Factory::create();
    }
    
    function await(PromiseInterface $promise, float $timeout = 10.0) {
        return await($promise, $this->loop, $timeout);
    }
}
