<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL\Tests;

class StatisticsCommandTest extends TestCase {
    function testGetEncodedMessage() {
        $command = new \Plasma\Drivers\MySQL\Commands\StatisticsCommand();
        $this->assertFalse($command->hasFinished());
        
        $this->assertSame(\chr(0x09), $command->getEncodedMessage());
        $this->assertFalse($command->hasFinished());
    }
    
    function testSetParserState() {
        $command = new \Plasma\Drivers\MySQL\Commands\StatisticsCommand();
        
        $this->assertSame(-1, $command->setParserState());
    }
    
    function testOnComplete() {
        $command = new \Plasma\Drivers\MySQL\Commands\StatisticsCommand();
        
        $this->assertNull($command->onComplete());
        $this->assertTrue($command->hasFinished());
    }
    
    function testOnError() {
        $command = new \Plasma\Drivers\MySQL\Commands\StatisticsCommand();
        
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
        $command = new \Plasma\Drivers\MySQL\Commands\StatisticsCommand();
        
        $deferred = new \React\Promise\Deferred();
        
        $command->on('end', function ($a) use (&$deferred) {
            $deferred->resolve($a);
        });
        
        $command->onNext((new \stdClass()));
        $this->assertTrue($command->hasFinished());
        
        $a = $this->await($deferred->promise(), 0.1);
        $this->assertInstanceOf(\stdClass::class, $a);
    }
    
    function testWaitForCompletion() {
        $command = new \Plasma\Drivers\MySQL\Commands\StatisticsCommand();
        $this->assertTrue($command->waitForCompletion());
    }
    
    function testResetSequence() {
        $command = new \Plasma\Drivers\MySQL\Commands\StatisticsCommand();
        $this->assertTrue($command->resetSequence());
    }
}
