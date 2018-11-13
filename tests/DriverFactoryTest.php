<?php
/**
 * Plasma Core component
 * Copyright 2018 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/core/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL\Tests;

class DriverFactoryTest extends TestCase {
    /**
     * @var \Plasma\Drivers\MySQL\DriverFactory
     */
    public $factory;
    
    function setUp() {
        parent::setUp();
        $this->factory = new \Plasma\Drivers\MySQL\DriverFactory($this->loop);
    }
    
    
}
