<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
 * @noinspection PhpUnhandledExceptionInspection
*/

namespace Plasma\Drivers\MySQL\Tests\Messages;

use Plasma\Drivers\MySQL\Messages\ParseException;
use Plasma\Drivers\MySQL\Tests\TestCase;

class ParseExceptionTest extends TestCase {
    function testGetState() {
        $exc = new ParseException('');
        self::assertNull($exc->getState());
    }
    
    function testSetState() {
        $exc = new ParseException('');
        
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        self::assertNull($exc->setState(5));
        self::assertSame(5, $exc->getState());
    }
    
    function testGetBuffer() {
        $exc = new ParseException('');
        self::assertNull($exc->getBuffer());
    }
    
    function testSetBuffer() {
        $exc = new ParseException('');
        
        $exc->setBuffer('hello world');
        self::assertSame('hello world', $exc->getBuffer());
    }
}
