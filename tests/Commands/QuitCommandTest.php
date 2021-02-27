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

use Plasma\Drivers\MySQL\Commands\QuitCommand;
use Plasma\Drivers\MySQL\Tests\TestCase;
use React\Promise\Deferred;

class QuitCommandTest extends TestCase {
    function testGetEncodedMessage() {
        $command = new QuitCommand();
        self::assertFalse($command->hasFinished());
        
        self::assertSame("\x01", $command->getEncodedMessage());
        self::assertTrue($command->hasFinished());
    }
    
    function testSetParserState() {
        $command = new QuitCommand();
        
        self::assertSame(0, $command->setParserState());
    }
    
    function testOnComplete() {
        $command = new QuitCommand();
        
        $deferred = new Deferred();
        
        $command->on('end', function ($a = null) use (&$deferred) {
            $deferred->resolve($a);
        });
        
        $command->onComplete();
        
        $a = $this->await($deferred->promise(), 0.1);
        self::assertNull($a);
    }
    
    function testOnError() {
        $command = new QuitCommand();
        
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
        $command = new QuitCommand();
        
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        self::assertNull($command->onNext(null));
    }
    
    function testWaitForCompletion() {
        $command = new QuitCommand();
        self::assertTrue($command->waitForCompletion());
    }
    
    function testResetSequence() {
        $command = new QuitCommand();
        self::assertTrue($command->resetSequence());
    }
}
