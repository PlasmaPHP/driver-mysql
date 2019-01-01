<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL\Tests\Commands;

class SSLRequestCommandTest extends \Plasma\Drivers\MySQL\Tests\TestCase {
    function testGetEncodedMessage() {
        $parser = $this->getMockBuilder(\Plasma\Drivers\MySQL\ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $handshake = new \Plasma\Drivers\MySQL\Messages\HandshakeMessage($parser);
        
        $command = new \Plasma\Drivers\MySQL\Commands\SSLRequestCommand($handshake, 420);
        $this->assertFalse($command->hasFinished());
        
        $maxPacketSize = \Plasma\Drivers\MySQL\ProtocolParser::CLIENT_MAX_PACKET_SIZE;
        $charsetNumber = \Plasma\Drivers\MySQL\ProtocolParser::CLIENT_CHARSET_NUMBER;
        
        $packet = \pack('VVc', 420, $maxPacketSize, $charsetNumber);
        $packet .= \str_repeat("\x00", 23);
        
        $this->assertSame($packet, $command->getEncodedMessage());
        $this->assertTrue($command->hasFinished());
    }
    
    function testSetParserState() {
        $parser = $this->getMockBuilder(\Plasma\Drivers\MySQL\ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $handshake = new \Plasma\Drivers\MySQL\Messages\HandshakeMessage($parser);
        $command = new \Plasma\Drivers\MySQL\Commands\SSLRequestCommand($handshake, 420);
        
        $this->assertSame(\Plasma\Drivers\MySQL\ProtocolParser::STATE_HANDSHAKE, $command->setParserState());
    }
    
    function testOnComplete() {
        $parser = $this->getMockBuilder(\Plasma\Drivers\MySQL\ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $handshake = new \Plasma\Drivers\MySQL\Messages\HandshakeMessage($parser);
        $command = new \Plasma\Drivers\MySQL\Commands\SSLRequestCommand($handshake, 420);
        
        $deferred = new \React\Promise\Deferred();
        
        $command->on('end', function ($a = null) use (&$deferred) {
            $deferred->resolve($a);
        });
        
        $command->onComplete();
        
        $a = $this->await($deferred->promise(), 0.1);
        $this->assertNull($a);
    }
    
    function testOnError() {
        $parser = $this->getMockBuilder(\Plasma\Drivers\MySQL\ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $handshake = new \Plasma\Drivers\MySQL\Messages\HandshakeMessage($parser);
        $command = new \Plasma\Drivers\MySQL\Commands\SSLRequestCommand($handshake, 420);
        
        $deferred = new \React\Promise\Deferred();
        
        $command->on('error', function (\Throwable $e) use (&$deferred) {
            $deferred->reject($e);
        });
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('test');
                
        $command->onError((new \RuntimeException('test')));
        $this->await($deferred->promise(), 0.1);
    }
    
    function testOnNext() {
        $parser = $this->getMockBuilder(\Plasma\Drivers\MySQL\ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $handshake = new \Plasma\Drivers\MySQL\Messages\HandshakeMessage($parser);
        $command = new \Plasma\Drivers\MySQL\Commands\SSLRequestCommand($handshake, 420);
        
        $this->assertNull($command->onNext(null));
    }
    
    function testWaitForCompletion() {
        $parser = $this->getMockBuilder(\Plasma\Drivers\MySQL\ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $handshake = new \Plasma\Drivers\MySQL\Messages\HandshakeMessage($parser);
        $command = new \Plasma\Drivers\MySQL\Commands\SSLRequestCommand($handshake, 420);
        
        $this->assertFalse($command->waitForCompletion());
    }
    
    function testResetSequence() {
        $parser = $this->getMockBuilder(\Plasma\Drivers\MySQL\ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $handshake = new \Plasma\Drivers\MySQL\Messages\HandshakeMessage($parser);
        $command = new \Plasma\Drivers\MySQL\Commands\SSLRequestCommand($handshake, 420);
        
        $this->assertFalse($command->resetSequence());
    }
}
