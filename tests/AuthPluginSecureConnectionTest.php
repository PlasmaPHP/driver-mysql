<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL\Tests;

class AuthPluginSecureConnectionTest extends TestCase {
    function testGetHandshakeAuth() {
        $parser = $this->getMockBuilder(\Plasma\Drivers\MySQL\ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $handshake = new \Plasma\Drivers\MySQL\Messages\HandshakeMessage($parser);
        $handshake->scramble = '_hello world of rub_';
        
        $password = 'plasma-mysql';
        $correctHash = \hex2bin('14bd5a77488737773b19a763e34af0bdcc6d08d916');
        
        $auth = new \Plasma\Drivers\MySQL\AuthPlugins\AuthSecureConnection($parser, $handshake);
        $output = $auth->getHandshakeAuth($password);
        
        $this->assertSame($correctHash, $output);
    }
    
    function testGetHandshakeAuthEmptyPassword() {
        $parser = $this->getMockBuilder(\Plasma\Drivers\MySQL\ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $handshake = new \Plasma\Drivers\MySQL\Messages\HandshakeMessage($parser);
        $handshake->scramble = '_hello world of rub_';
        
        $auth = new \Plasma\Drivers\MySQL\AuthPlugins\AuthSecureConnection($parser, $handshake);
        $output = $auth->getHandshakeAuth('');
        
        $this->assertSame("\x00", $output);
    }
    
    function testAuthMoreData() {
        $parser = $this->getMockBuilder(\Plasma\Drivers\MySQL\ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $handshake = new \Plasma\Drivers\MySQL\Messages\HandshakeMessage($parser);
        
        $auth = new \Plasma\Drivers\MySQL\AuthPlugins\AuthSecureConnection($parser, $handshake);
        $moreData = new \Plasma\Drivers\MySQL\Messages\AuthMoreDataMessage($parser);
        
        $this->expectException(\Plasma\Exception::class);
        $auth->receiveMoreData($moreData);
    }
}
