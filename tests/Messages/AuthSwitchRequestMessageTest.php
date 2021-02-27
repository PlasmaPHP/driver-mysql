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
use Plasma\Drivers\MySQL\Messages\AuthSwitchRequestMessage;
use Plasma\Drivers\MySQL\ProtocolParser;
use Plasma\Drivers\MySQL\Tests\TestCase;

class AuthSwitchRequestMessageTest extends TestCase {
    function testGetID() {
        $parser = $this->getMockBuilder(ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $message = new AuthSwitchRequestMessage($parser);
        
        /** @noinspection StaticInvocationViaThisInspection */
        self::assertSame("\xFE", $message->getID());
    }
    
    function testParseMessage() {
        $parser = $this->getMockBuilder(ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $message = new AuthSwitchRequestMessage($parser);
        
        $buffer = new BinaryBuffer();
        $buffer->append(__FILE__."\x00hello");
        
        self::assertTrue($message->parseMessage($buffer));
        self::assertSame(__FILE__, $message->authPluginName);
        self::assertSame('hello', $message->authPluginData);
    }
    
    function testGetParser() {
        $parser = $this->getMockBuilder(ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $message = new AuthSwitchRequestMessage($parser);
        self::assertSame($parser, $message->getParser());
    }
    
    function testSetParserState() {
        $parser = $this->getMockBuilder(ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $message = new AuthSwitchRequestMessage($parser);
        self::assertSame(ProtocolParser::STATE_AUTH_SENT, $message->setParserState());
    }
}
