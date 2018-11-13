<?php
/**
 * Plasma Core component
 * Copyright 2018 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/core/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL\Tests;

class DriverTest extends TestCase {
    /**
     * @var \Plasma\Drivers\MySQL\DriverFactory
     */
    public $factory;
    
    /**
     * @var \Plasma\Drivers\MySQL\Driver
     */
     public $driver;
    
    function setUp() {
        parent::setUp();
        $this->factory = new \Plasma\Drivers\MySQL\DriverFactory($this->loop, array());
    }
    
    function connect(\Plasma\DriverInterface $driver, string $uri): \React\Promise\PromiseInterface {
        $creds = (getenv('MDB_USER') ? getenv('MDB_USER').':'.getenv('MDB_PASSWORD').'@' : '');
        
        return $driver->connect($creds.$uri);
    }
    
    function testGetLoop() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $loop = $driver->getLoop();
        $this->assertInstanceOf(\React\EventLoop\LoopInterface::class, $loop);
        $this->assertSame($this->loop, $loop);
    }
    
    function testGetConnectionState() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $state = $driver->getConnectionState();
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_CLOSED, $state);
    }
    
    function testGetBusyState() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $state = $driver->getBusyState();
        $this->assertSame(\Plasma\DriverInterface::STATE_IDLE, $state);
    }
    
    function testGetBacklogLength() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $state = $driver->getBacklogLength();
        $this->assertSame(0, $state);
        
        $client = $this->createClientMock();
        
        $client
            ->expects($this->once())
            ->method('checkinConnection')
            ->with($driver);
        
        $ping = new \Plasma\Drivers\MySQL\Commands\PingCommand();
        $driver->runCommand($client, $ping);
        
        $state = $driver->getBacklogLength();
        $this->assertSame(1, $state);
    }
    
    function testConnect() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost');
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->await($prom);
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_OK, $driver->getConnectionState());
    }
    
    function testConnectWithPort() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:3306');
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->await($prom);
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_OK, $driver->getConnectionState());
    }
    
    function testConnectInvalidCredentials() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $driver->connect('root:abc-never@localhost');
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->expectException(\InvalidArgumentException::class); // for testing
        $this->await($prom);
    }
    
    function testConnectForceTLS() {
        $factory = new \Plasma\Drivers\MySQL\DriverFactory($this->loop, array('tls.force' => true, 'tls.ignoreIPs' => array()));
        $driver = $factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost');
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->await($prom);
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_OK, $driver->getConnectionState());
        
    }
    
    function testConnectForceTLSFailure() {
        $factory = new \Plasma\Drivers\MySQL\DriverFactory($this->loop, array('tls.force' => true, 'tls.ignoreIPs' => array()));
        $driver = $factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost');
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->expectException(\InvalidArgumentException::class); // for testing
        $this->await($prom);
    }
    
    function testConnectForceTLSPrivate() {
        $factory = new \Plasma\Drivers\MySQL\DriverFactory($this->loop, array('tls.force' => true, 'tls.ignoreIPs' => array()));
        $driver = $factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost');
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->await($prom);
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_OK, $driver->getConnectionState());
    }
    
    function testConnectForceTLSPrivateIgnored() {
        $factory = new \Plasma\Drivers\MySQL\DriverFactory($this->loop, array('tls.force' => true, 'tls.ignoreIPs' => array()));
        $driver = $factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, '127.0.0.1');
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->await($prom);
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_OK, $driver->getConnectionState());
    }
    
    function testPauseStreamConsumption() {
        $this->markTestSkipped('Not implemented yet');
    }
    
    function testResumeStreamConsumption() {
        $this->markTestSkipped('Not implemented yet');
    }
    
    function testClose() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, '127.0.0.1');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $client
            ->expects($this->once())
            ->method('checkinConnection')
            ->with($driver);
        
        $ping = new \Plasma\Drivers\MySQL\Commands\PingCommand();
        $promC = $driver->runCommand($client, $ping);
        
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $promC);
        $this->await($promC);
        
        $prom2 = $this->close($driver->close());
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom2);
        
        $this->await($prom2);
    }
    
    function testQuit() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $deferred = new \React\Promise\Deferred();
        
        $driver->once('close', function () use (&$deferred) {
            $deferred->resolve();
        });
        
        $prom = $this->connect($driver, '127.0.0.1');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $client
            ->expects($this->once())
            ->method('checkinConnection')
            ->with($driver);
        
        $ping = new \Plasma\Drivers\MySQL\Commands\PingCommand();
        $promC = $driver->runCommand($client, $ping);
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $promC);
        
        $prom2 = $this->quit($driver->close());
        $this->assertNull($prom2);
        
        try {
            $this->assertInstanceOf(\Throwable::class, $this->await($promC));
        } catch (\Plasma\Exception $e) {
            $this->assertInstanceOf(\Plasma\Exception::class, $e);
        }
        
        $this->await($deferred->promise());
    }
    
    function testTransaction() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost');
        $this->await($prom);
        
        $this->assertFalse($driver->isInTransaction());
        
        $client = $this->createClientMock();
        
        $client
            ->expects($this->once())
            ->method('checkinConnection')
            ->with($driver);
        
        $prom2 = $driver->beginTransaction($client, \Plasma\TransactionInterface::ISOLATION_COMMITTED);
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom2);
        
        $transaction = $this->await($prom2);
        $this->assertInstanceof(\Plasma\TransactionInterface::class, $transaction);
        
        $this->assertTrue($driver->isInTransaction());
        
        $prom3 = $transaction->rollback();
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom3);
        
        $this->await($prom3);
        
        $this->assertFalse($driver->isInTransaction());
    }
    
    function testRunCommand() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $client
            ->expects($this->exactly(2))
            ->method('checkinConnection')
            ->with($driver);
        
        $ping = new \Plasma\Drivers\MySQL\Commands\PingCommand();
        $promC = $driver->runCommand($client, $ping);
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $promC);
        
        $this->await($promC);
        
        $ping2 = (new class() extends \Plasma\Drivers\MySQL\Commands\PingCommand {
            function onComplete(): void {
                $this->finished = true;
                $this->emit('error', array((new \LogicException('test'))));
            }
        });
        
        $promC2 = $driver->runCommand($client, $ping2);
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $promC2);
        
        $this->expectException(\LogicException::class);
        $this->await($promC2);
    }
    
    function createClientMock(): \Plasma\ClientInterface {
        return $this->getMockBuilder(\Plasma\ClientInterface::class)
            ->setMethods(array(
                'getConnectionCount',
                'beginTransaction',
                'checkinConnection',
                'close',
                'quit',
                'runCommand'
            ))
            ->getMock();
    }
}
