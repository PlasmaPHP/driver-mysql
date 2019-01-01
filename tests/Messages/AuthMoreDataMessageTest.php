<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL\Tests\Messages;

class AuthMoreDataMessageTest extends \Plasma\Drivers\MySQL\Tests\TestCase {
    function testGetID() {
        $parser = $this->getMockBuilder(\Plasma\Drivers\MySQL\ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $message = new \Plasma\Drivers\MySQL\Messages\AuthMoreDataMessage($parser);
        $this->assertSame("\x01", $message->getID());
    }
    
    function testParseMessage() {
        $parser = $this->getMockBuilder(\Plasma\Drivers\MySQL\ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $message = new \Plasma\Drivers\MySQL\Messages\AuthMoreDataMessage($parser);
        
        $buffer = new \Plasma\BinaryBuffer();
        $buffer->append(__FILE__);
        
        $this->assertTrue($message->parseMessage($buffer));
        $this->assertSame(__FILE__, $message->authPluginData);
    }
    
    function testGetParser() {
        $parser = $this->getMockBuilder(\Plasma\Drivers\MySQL\ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $message = new \Plasma\Drivers\MySQL\Messages\AuthMoreDataMessage($parser);
        $this->assertSame($parser, $message->getParser());
    }
    
    function testSetParserState() {
        $parser = $this->getMockBuilder(\Plasma\Drivers\MySQL\ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $message = new \Plasma\Drivers\MySQL\Messages\AuthMoreDataMessage($parser);
        $this->assertSame(\Plasma\Drivers\MySQL\ProtocolParser::STATE_AUTH, $message->setParserState());
    }
}
