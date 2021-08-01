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

use Plasma\Drivers\MySQL\Commands\ResetConnectionCommand;
use Plasma\Drivers\MySQL\Tests\TestCase;
use React\Promise\Deferred;

class ResetConnectionCommandTest extends TestCase {
    function testGetEncodedMessage() {
        $command = new ResetConnectionCommand();
        self::assertFalse($command->hasFinished());
        
        self::assertSame("\x1F", $command->getEncodedMessage());
        self::assertTrue($command->hasFinished());
    }
    
    function testSetParserState() {
        $command = new ResetConnectionCommand();
        
        self::assertSame(-1, $command->setParserState());
    }
    
    function testOnComplete() {
        $command = new ResetConnectionCommand();
        
        $deferred = new Deferred();
        
        $command->on('end', function ($a = null) use (&$deferred) {
            $deferred->resolve($a);
        });
        
        $command->onComplete();
        
        $a = $this->await($deferred->promise(), 0.1);
        self::assertNull($a);
    }
    
    function testOnError() {
        $command = new ResetConnectionCommand();
        
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
        $command = new ResetConnectionCommand();
        
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        self::assertNull($command->onNext(null));
    }
    
    function testWaitForCompletion() {
        $command = new ResetConnectionCommand();
        self::assertTrue($command->waitForCompletion());
    }
    
    function testResetSequence() {
        $command = new ResetConnectionCommand();
        self::assertTrue($command->resetSequence());
    }
}
