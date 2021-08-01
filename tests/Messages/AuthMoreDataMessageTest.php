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

use Plasma\BinaryBuffer;
use Plasma\Drivers\MySQL\Messages\AuthMoreDataMessage;
use Plasma\Drivers\MySQL\ProtocolParser;
use Plasma\Drivers\MySQL\Tests\TestCase;

class AuthMoreDataMessageTest extends TestCase {
    function testGetID() {
        $parser = $this->getMockBuilder(ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $message = new AuthMoreDataMessage($parser);
        
        /** @noinspection StaticInvocationViaThisInspection */
        self::assertSame("\x01", $message->getID());
    }
    
    function testParseMessage() {
        $parser = $this->getMockBuilder(ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $message = new AuthMoreDataMessage($parser);
        
        $buffer = new BinaryBuffer();
        $buffer->append(__FILE__);
        
        self::assertTrue($message->parseMessage($buffer));
        self::assertSame(__FILE__, $message->authPluginData);
    }
    
    function testGetParser() {
        $parser = $this->getMockBuilder(ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $message = new AuthMoreDataMessage($parser);
        self::assertSame($parser, $message->getParser());
    }
    
    function testSetParserState() {
        $parser = $this->getMockBuilder(ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $message = new AuthMoreDataMessage($parser);
        self::assertSame(ProtocolParser::STATE_AUTH, $message->setParserState());
    }
}
