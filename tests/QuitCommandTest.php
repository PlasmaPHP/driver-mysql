<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL\Tests;

class QuitCommandTest extends TestCase {
    function testGetEncodedMessage() {
        $command = new \Plasma\Drivers\MySQL\Commands\QuitCommand();
        $this->assertFalse($command->hasFinished());
        
        $this->assertSame("\x01", $command->getEncodedMessage());
        $this->assertTrue($command->hasFinished());
    }
    
    function testSetParserState() {
        $command = new \Plasma\Drivers\MySQL\Commands\QuitCommand();
        
        $this->assertSame(-1, $command->setParserState());
    }
    
    function testOnComplete() {
        $command = new \Plasma\Drivers\MySQL\Commands\QuitCommand();
        
        $deferred = new \React\Promise\Deferred();
        
        $command->on('end', function ($a = null) use (&$deferred) {
            $deferred->resolve($a);
        });
        
        $command->onComplete();
        
        $a = $this->await($deferred->promise(), 0.1);
        $this->assertNull($a);
    }
    
    function testOnError() {
        $command = new \Plasma\Drivers\MySQL\Commands\QuitCommand();
        
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
        $command = new \Plasma\Drivers\MySQL\Commands\QuitCommand();
        $this->assertNull($command->onNext(null));
    }
    
    function testWaitForCompletion() {
        $command = new \Plasma\Drivers\MySQL\Commands\QuitCommand();
        $this->assertTrue($command->waitForCompletion());
    }
    
    function testResetSequence() {
        $command = new \Plasma\Drivers\MySQL\Commands\QuitCommand();
        $this->assertTrue($command->resetSequence());
    }
}
