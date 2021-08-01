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

use Plasma\Drivers\MySQL\AuthPlugins\AuthSecureConnection;
use Plasma\Drivers\MySQL\Messages\AuthMoreDataMessage;
use Plasma\Drivers\MySQL\Messages\HandshakeMessage;
use Plasma\Drivers\MySQL\ProtocolParser;
use Plasma\Exception;

class AuthPluginSecureConnectionTest extends TestCase {
    function testGetHandshakeAuth() {
        $parser = $this->getMockBuilder(ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $handshake = new HandshakeMessage($parser);
        $handshake->scramble = '_hello world of rub_';
        
        $password = 'plasma-mysql';
        $correctHash = \hex2bin('14bd5a77488737773b19a763e34af0bdcc6d08d916');
        
        $auth = new AuthSecureConnection($parser, $handshake);
        $output = $auth->getHandshakeAuth($password);
        
        self::assertSame($correctHash, $output);
    }
    
    function testGetHandshakeAuthEmptyPassword() {
        $parser = $this->getMockBuilder(ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $handshake = new HandshakeMessage($parser);
        $handshake->scramble = '_hello world of rub_';
        
        $auth = new AuthSecureConnection($parser, $handshake);
        $output = $auth->getHandshakeAuth('');
        
        self::assertSame("\x00", $output);
    }
    
    function testAuthMoreData() {
        $parser = $this->getMockBuilder(ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $handshake = new HandshakeMessage($parser);
        
        $auth = new AuthSecureConnection($parser, $handshake);
        $moreData = new AuthMoreDataMessage($parser);
        
        $this->expectException(Exception::class);
        $auth->receiveMoreData($moreData);
    }
}
