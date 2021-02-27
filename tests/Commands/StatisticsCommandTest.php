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

use Plasma\Drivers\MySQL\Commands\StatisticsCommand;
use Plasma\Drivers\MySQL\Tests\TestCase;
use React\Promise\Deferred;

class StatisticsCommandTest extends TestCase {
    function testGetEncodedMessage() {
        $command = new StatisticsCommand();
        self::assertFalse($command->hasFinished());
        
        self::assertSame("\x09", $command->getEncodedMessage());
        self::assertFalse($command->hasFinished());
    }
    
    function testSetParserState() {
        $command = new StatisticsCommand();
        
        self::assertSame(-1, $command->setParserState());
    }
    
    function testOnComplete() {
        $command = new StatisticsCommand();
        
        $command->on('end', function () {
            throw new \LogicException('Unexpected end event was emitted');
        });
        
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        self::assertNull($command->onComplete());
        self::assertTrue($command->hasFinished());
    }
    
    function testOnError() {
        $command = new StatisticsCommand();
        
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
        $command = new StatisticsCommand();
        
        $deferred = new Deferred();
        
        $command->on('end', function ($a) use (&$deferred) {
            $deferred->resolve($a);
        });
        
        $command->onNext((new \stdClass()));
        self::assertTrue($command->hasFinished());
        
        $a = $this->await($deferred->promise(), 0.1);
        self::assertInstanceOf(\stdClass::class, $a);
    }
    
    function testWaitForCompletion() {
        $command = new StatisticsCommand();
        self::assertTrue($command->waitForCompletion());
    }
    
    function testResetSequence() {
        $command = new StatisticsCommand();
        self::assertTrue($command->resetSequence());
    }
}
