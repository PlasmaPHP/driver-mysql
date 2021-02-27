<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
 * @noinspection PhpUnhandledExceptionInspection
*/

namespace Plasma\Drivers\MySQL\Tests\Commands;

use Plasma\Drivers\MySQL\Commands\SSLRequestCommand;
use Plasma\Drivers\MySQL\Messages\HandshakeMessage;
use Plasma\Drivers\MySQL\ProtocolParser;
use Plasma\Drivers\MySQL\Tests\TestCase;
use React\Promise\Deferred;

class SSLRequestCommandTest extends TestCase {
    function testGetEncodedMessage() {
        $parser = $this->getMockBuilder(ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $handshake = new HandshakeMessage($parser);
        
        $command = new SSLRequestCommand($handshake, 420);
        self::assertFalse($command->hasFinished());
        
        $maxPacketSize = ProtocolParser::CLIENT_MAX_PACKET_SIZE;
        $charsetNumber = ProtocolParser::CLIENT_CHARSET_NUMBER;
        
        $packet = \pack('VVc', 420, $maxPacketSize, $charsetNumber);
        $packet .= \str_repeat("\x00", 23);
        
        self::assertSame($packet, $command->getEncodedMessage());
        self::assertTrue($command->hasFinished());
    }
    
    function testSetParserState() {
        $parser = $this->getMockBuilder(ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $handshake = new HandshakeMessage($parser);
        $command = new SSLRequestCommand($handshake, 420);
        
        self::assertSame(ProtocolParser::STATE_HANDSHAKE, $command->setParserState());
    }
    
    function testOnComplete() {
        $parser = $this->getMockBuilder(ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $handshake = new HandshakeMessage($parser);
        $command = new SSLRequestCommand($handshake, 420);
        
        $deferred = new Deferred();
        
        $command->on('end', function ($a = null) use (&$deferred) {
            $deferred->resolve($a);
        });
        
        $command->onComplete();
        
        $a = $this->await($deferred->promise(), 0.1);
        self::assertNull($a);
    }
    
    function testOnError() {
        $parser = $this->getMockBuilder(ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $handshake = new HandshakeMessage($parser);
        $command = new SSLRequestCommand($handshake, 420);
        
        $deferred = new Deferred();
        
        $command->on('error', function (\Throwable $e) use (&$deferred) {
            $deferred->reject($e);
        });
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('test');
                
        $command->onError((new \RuntimeException('test')));
        $this->await($deferred->promise(), 0.1);
    }
    
    function testOnNext() {
        $parser = $this->getMockBuilder(ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $handshake = new HandshakeMessage($parser);
        $command = new SSLRequestCommand($handshake, 420);
        
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        self::assertNull($command->onNext(null));
    }
    
    function testWaitForCompletion() {
        $parser = $this->getMockBuilder(ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $handshake = new HandshakeMessage($parser);
        $command = new SSLRequestCommand($handshake, 420);
        
        self::assertFalse($command->waitForCompletion());
    }
    
    function testResetSequence() {
        $parser = $this->getMockBuilder(ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $handshake = new HandshakeMessage($parser);
        $command = new SSLRequestCommand($handshake, 420);
        
        self::assertFalse($command->resetSequence());
    }
}
