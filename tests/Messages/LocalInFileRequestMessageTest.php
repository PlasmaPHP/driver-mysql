<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL\Tests\Messages;

class LocalInFileRequestMessageTest extends \Plasma\Drivers\MySQL\Tests\TestCase {
    function testGetID() {
        $parser = $this->getMockBuilder(\Plasma\Drivers\MySQL\ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $message = new \Plasma\Drivers\MySQL\Messages\LocalInFileRequestMessage($parser);
        $this->assertSame("\xFB", $message->getID());
    }
    
    function testParseMessage() {
        $parser = $this->getMockBuilder(\Plasma\Drivers\MySQL\ProtocolParser::class)
            ->disableOriginalConstructor()
            ->setMethods(array(
                'sendPacket'
            ))
            ->getMock();
        
        $parser
            ->expects($this->at(0))
            ->method('sendPacket')
            ->with(\file_get_contents(__FILE__));
        
        $parser
            ->expects($this->at(1))
            ->method('sendPacket')
            ->with('');
        
        $message = new \Plasma\Drivers\MySQL\Messages\LocalInFileRequestMessage($parser);
        
        $buffer = new \Plasma\BinaryBuffer();
        $buffer->append(__FILE__);
        
        $this->assertTrue($message->parseMessage($buffer));
    }
    
    function testGetParser() {
        $parser = $this->getMockBuilder(\Plasma\Drivers\MySQL\ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $message = new \Plasma\Drivers\MySQL\Messages\LocalInFileRequestMessage($parser);
        $this->assertSame($parser, $message->getParser());
    }
    
    function testSetParserState() {
        $parser = $this->getMockBuilder(\Plasma\Drivers\MySQL\ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $message = new \Plasma\Drivers\MySQL\Messages\LocalInFileRequestMessage($parser);
        $this->assertSame(-1, $message->setParserState());
    }
}
