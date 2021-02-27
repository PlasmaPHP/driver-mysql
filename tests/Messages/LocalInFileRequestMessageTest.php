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
use Plasma\Drivers\MySQL\Messages\LocalInFileRequestMessage;
use Plasma\Drivers\MySQL\ProtocolParser;
use Plasma\Drivers\MySQL\Tests\TestCase;

class LocalInFileRequestMessageTest extends TestCase {
    function testGetID() {
        $parser = $this->getMockBuilder(ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $message = new LocalInFileRequestMessage($parser);
        
        /** @noinspection StaticInvocationViaThisInspection */
        self::assertSame("\xFB", $message->getID());
    }
    
    function testParseMessage() {
        $parser = $this->getMockBuilder(ProtocolParser::class)
            ->disableOriginalConstructor()
            ->setMethods(array(
                'sendPacket'
            ))
            ->getMock();
        
        $parser
            ->expects(self::at(0))
            ->method('sendPacket')
            ->with(\file_get_contents(__FILE__));
        
        $parser
            ->expects(self::at(1))
            ->method('sendPacket')
            ->with('');
        
        $message = new LocalInFileRequestMessage($parser);
        
        $buffer = new BinaryBuffer();
        $buffer->append(__FILE__);
        
        self::assertTrue($message->parseMessage($buffer));
    }
    
    function testGetParser() {
        $parser = $this->getMockBuilder(ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $message = new LocalInFileRequestMessage($parser);
        self::assertSame($parser, $message->getParser());
    }
    
    function testSetParserState() {
        $parser = $this->getMockBuilder(ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $message = new LocalInFileRequestMessage($parser);
        self::assertSame(-1, $message->setParserState());
    }
}
