<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL\Tests\Commands;

class ResetConnectionCommandTest extends \Plasma\Drivers\MySQL\Tests\TestCase {
    function testGetEncodedMessage() {
        $command = new \Plasma\Drivers\MySQL\Commands\ResetConnectionCommand();
        $this->assertFalse($command->hasFinished());
        
        $this->assertSame("\x1F", $command->getEncodedMessage());
        $this->assertTrue($command->hasFinished());
    }
    
    function testSetParserState() {
        $command = new \Plasma\Drivers\MySQL\Commands\ResetConnectionCommand();
        
        $this->assertSame(-1, $command->setParserState());
    }
    
    function testOnComplete() {
        $command = new \Plasma\Drivers\MySQL\Commands\ResetConnectionCommand();
        
        $deferred = new \React\Promise\Deferred();
        
        $command->on('end', function ($a = null) use (&$deferred) {
            $deferred->resolve($a);
        });
        
        $command->onComplete();
        
        $a = $this->await($deferred->promise(), 0.1);
        $this->assertNull($a);
    }
    
    function testOnError() {
        $command = new \Plasma\Drivers\MySQL\Commands\ResetConnectionCommand();
        
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
        $command = new \Plasma\Drivers\MySQL\Commands\ResetConnectionCommand();
        $this->assertNull($command->onNext(null));
    }
    
    function testWaitForCompletion() {
        $command = new \Plasma\Drivers\MySQL\Commands\ResetConnectionCommand();
        $this->assertTrue($command->waitForCompletion());
    }
    
    function testResetSequence() {
        $command = new \Plasma\Drivers\MySQL\Commands\ResetConnectionCommand();
        $this->assertTrue($command->resetSequence());
    }
}
