<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL\Tests\Messages;

class ParseExceptionTest extends \Plasma\Drivers\MySQL\Tests\TestCase {
    function testGetState() {
        $exc = new \Plasma\Drivers\MySQL\Messages\ParseException('');
        $this->assertNull($exc->getState());
    }
    
    function testSetState() {
        $exc = new \Plasma\Drivers\MySQL\Messages\ParseException('');
        
        $this->assertNull($exc->setState(5));
        $this->assertSame(5, $exc->getState());
    }
    
    function testGetBuffer() {
        $exc = new \Plasma\Drivers\MySQL\Messages\ParseException('');
        $this->assertNull($exc->getBuffer());
    }
    
    function testSetBuffer() {
        $exc = new \Plasma\Drivers\MySQL\Messages\ParseException('');
        
        $exc->setBuffer('hello world');
        $this->assertSame('hello world', $exc->getBuffer());
    }
}
