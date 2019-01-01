<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL\Tests;

class DriverTest extends TestCase {
    /**
     * @var \Plasma\Drivers\MySQL\DriverFactory
     */
    public $factory;
    
    function setUp() {
        parent::setUp();
        $this->factory = new \Plasma\Drivers\MySQL\DriverFactory($this->loop, array());
    }
    
    function connect(\Plasma\DriverInterface $driver, string $uri, string $scheme = 'tcp'): \React\Promise\PromiseInterface {
        $creds = (\getenv('MDB_USER') ? \getenv('MDB_USER').':'.\getenv('MDB_PASSWORD').'@' : 'root:@');
        
        return $driver->connect($scheme.'://'.$creds.$uri);
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
            ->expects($this->any())
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
        
        $prom = $this->connect($driver, 'localhost', 'mysql');
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->await($prom);
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_OK, $driver->getConnectionState());
        
        $prom2 = $this->connect($driver, 'localhost');
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom2);
    }
    
    function testConnectWithPort() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306));
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $prom2 = $this->connect($driver, 'localhost');
        $this->assertSame($prom, $prom2);
        
        $this->await($prom);
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_OK, $driver->getConnectionState());
    }
    
    function testConnectInvalidCredentials() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $driver->connect('root:abc-never@localhost');
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->expectException(\Plasma\Exception::class);
        $this->expectExceptionMessageRegExp('/^Access denied for user/i');
        
        $this->await($prom);
    }
    
    function testConnectInvalidHost() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $driver->connect('');
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->await($prom);
    }
    
    function testConnectInvalidScheme() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $driver->connect('dns://localhost');
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $this->expectException(\InvalidArgumentException::class);
        $this->await($prom);
    }
    
    /**
     * @group tls
     */
    function testConnectForceTLSLocalhost() {
        $factory = new \Plasma\Drivers\MySQL\DriverFactory($this->loop, array('tls.force' => true, 'tls.forceLocal' => true));
        $driver = $factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT_SECURE') ?: (\getenv('MDB_PORT') ?: 3306)));
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->await($prom);
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_OK, $driver->getConnectionState());
    }
    
    /**
     * @group tls
     */
    function testConnectForceTLSLocalhostIgnored() {
        $factory = new \Plasma\Drivers\MySQL\DriverFactory($this->loop, array('tls.force' => true));
        $driver = $factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306));
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->await($prom);
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_OK, $driver->getConnectionState());
    }
    
    /**
     * @group tls
     */
    function testConnectForceTLSIgnoredSecureServer() {
        $factory = new \Plasma\Drivers\MySQL\DriverFactory($this->loop, array('tls.force' => true));
        $driver = $factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT_SECURE') ?: (\getenv('MDB_PORT') ?: 3306)));
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->await($prom);
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_OK, $driver->getConnectionState());
    }
    
    /**
     * @group tls
     */
    function testConnectForceTLSFailure() {
        $factory = new \Plasma\Drivers\MySQL\DriverFactory($this->loop, array('tls.force' => true, 'tls.forceLocal' => true));
        $driver = $factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306));
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->expectException(\Plasma\Exception::class);
        $this->expectExceptionMessage('TLS is not supported by the server');
        
        $this->await($prom, 30.0);
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
        
        $prom = $this->connect($driver, '127.0.0.1:'.(\getenv('MDB_PORT') ?: 3306));
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $client
            ->expects($this->exactly(2))
            ->method('checkinConnection')
            ->with($driver);
        
        $ping = new \Plasma\Drivers\MySQL\Commands\PingCommand();
        $driver->runCommand($client, $ping);
        
        $promC = $driver->runCommand($client, $ping);
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $promC);
        
        $prom2 = $driver->close();
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom2);
        
        $this->await($promC);
        $this->await($prom2);
        
        $prom3 = $driver->close();
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom3);
    }
    
    function testQuit() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $deferred = new \React\Promise\Deferred();
        
        $driver->once('close', function () use (&$deferred) {
            $deferred->resolve();
        });
        
        $prom = $this->connect($driver, '127.0.0.1:'.(\getenv('MDB_PORT') ?: 3306));
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $client
            ->expects($this->exactly(2))
            ->method('checkinConnection')
            ->with($driver);
        
        $ping = new \Plasma\Drivers\MySQL\Commands\PingCommand();
        $driver->runCommand($client, $ping);
        
        $promC = $driver->runCommand($client, $ping);
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $promC);
        
        $this->assertNull($driver->quit());
        
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
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306));
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
    
    function testAlreadyInTransaction() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306));
        $this->await($prom);
        
        $this->assertFalse($driver->isInTransaction());
        
        $client = $this->createClientMock();
        
        $client
            ->expects($this->never())
            ->method('checkinConnection')
            ->with($driver);
        
        $prom2 = $driver->beginTransaction($client, \Plasma\TransactionInterface::ISOLATION_COMMITTED);
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom2);
        
        $transaction = $this->await($prom2);
        $this->assertInstanceof(\Plasma\TransactionInterface::class, $transaction);
        
        $this->expectException(\Plasma\Exception::class);
        $driver->beginTransaction($client, \Plasma\TransactionInterface::ISOLATION_COMMITTED);
    }
    
    function testRunCommand() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306));
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
    
    function testRunMultipleCommands() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306));
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $client
            ->expects($this->exactly(3))
            ->method('checkinConnection')
            ->with($driver);
        
        $ping = new \Plasma\Drivers\MySQL\Commands\PingCommand();
        $promC = $driver->runCommand($client, $ping);
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $promC);
        
        $ping2 = new \Plasma\Drivers\MySQL\Commands\PingCommand();
        $promC2 = $driver->runCommand($client, $ping2);
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $promC2);
        
        $ping3 = new \Plasma\Drivers\MySQL\Commands\PingCommand();
        $promC3 = $driver->runCommand($client, $ping3);
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $promC3);
        
        $resolval = array();
        
        $promC->then(function () use (&$resolval) {
            $resolval[] = 0;
        });
        
        $promC2->then(function () use (&$resolval) {
            $resolval[] = 1;
        });
        
        $promC3->then(function () use (&$resolval) {
            $resolval[] = 2;
        });
        
        $this->await(\React\Promise\all(array($promC, $promC2, $promC3)));
        $this->assertSame(array(0, 1, 2), $resolval);
    }
    
    function testQuery() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306).'/plasma_tmp');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $client
            ->expects($this->exactly(2))
            ->method('checkinConnection')
            ->with($driver);
        
        $prom = $driver->query($client, 'CREATE TABLE IF NOT EXISTS `tbl_tmp` (`test` VARCHAR(50) NOT NULL) ENGINE = InnoDB');
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $res = $this->await($prom);
        $this->assertInstanceOf(\Plasma\QueryResultInterface::class, $res);
        
        $prom2 = $driver->query($client, 'SHOW DATABASES');
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom2);
        
        $res2 = $this->await($prom2);
        $this->assertInstanceOf(\Plasma\StreamQueryResultInterface::class, $res2);
        
        $data = null;
        $deferred = new \React\Promise\Deferred();
        
        $res2->once('close', function () use (&$deferred) {
            $deferred->resolve();
        });
        
        $res2->on('error', function (\Throwable $e) use (&$deferred) {
            $deferred->reject($e);
        });
        
        $res2->on('data', function ($row) use (&$data) {
            if($row['Database'] === 'plasma_tmp') {
                $data = $row;
            }
        });
        
        $this->await($deferred->promise());
        $this->assertSame(array('Database' => 'plasma_tmp'), $data);
    }
    
    function testQuerySelectedDatabase() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306).'/information_schema');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $client
            ->expects($this->once())
            ->method('checkinConnection')
            ->with($driver);
        
        $prom = $driver->query($client, 'SELECT DATABASE()');
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $res = $this->await($prom);
        $this->assertInstanceOf(\Plasma\StreamQueryResultInterface::class, $res);
        
        $data = null;
        $deferred = new \React\Promise\Deferred();
        
        $res->once('close', function () use (&$deferred) {
            $deferred->resolve();
        });
        
        $res->on('error', function (\Throwable $e) use (&$deferred) {
            $deferred->reject($e);
        });
        
        $res->on('data', function ($row) use (&$data) {
            $data = $row;
        });
        
        $this->await($deferred->promise());
        $this->assertSame(array('DATABASE()' => 'information_schema'), $data);
    }
    
    function testQueryConnectionCharset() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306).'/information_schema?charset=utf8mb4');
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->await($prom);
        $this->assertSame(\Plasma\DriverInterface::CONNECTION_OK, $driver->getConnectionState());
        
        $client = $this->createClientMock();
        
        $client
            ->expects($this->once())
            ->method('checkinConnection')
            ->with($driver);
        
        $prom = $driver->query($client, 'SHOW SESSION VARIABLES LIKE "character\_set\_%"');
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $res = $this->await($prom);
        $this->assertInstanceOf(\Plasma\StreamQueryResultInterface::class, $res);
        
        $data = null;
        $deferred = new \React\Promise\Deferred();
        
        $res->once('close', function () use (&$deferred) {
            $deferred->resolve();
        });
        
        $res->on('error', function (\Throwable $e) use (&$deferred) {
            $deferred->reject($e);
        });
        
        $res->on('data', function ($row) use (&$data) {
            if($row['Variable_name'] === 'character_set_connection') {
                $data = $row;
            }
        });
        
        $this->await($deferred->promise());
        $this->assertSame(array('Variable_name' => 'character_set_connection', 'Value' => 'utf8mb4'), $data);
    }
    
    function testPrepare() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306).'/information_schema');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $client
            ->expects($this->once())
            ->method('checkinConnection')
            ->with($driver);
        
        $prom = $driver->prepare($client, 'SELECT * FROM `SCHEMATA` WHERE `SCHEMA_NAME` = ?');
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $statement = $this->await($prom);
        $this->assertInstanceOf(\Plasma\StatementInterface::class, $statement);
        
        $prom2 = $statement->execute(array('plasma_tmp'));
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom2);
        
        $res = $this->await($prom2);
        $this->assertInstanceOf(\Plasma\StreamQueryResultInterface::class, $res);
        
        $data = null;
        $deferred = new \React\Promise\Deferred();
        
        $res->once('close', function () use (&$deferred) {
            $deferred->resolve();
        });
        
        $res->once('error', function (\Throwable $e) use (&$deferred) {
            $deferred->reject($e);
        });
        
        $res->on('data', function ($row) use (&$data) {
            if($row['SCHEMA_NAME'] === 'plasma_tmp') {
                $data = $row;
            }
        });
        
        $this->await($deferred->promise());
        $this->assertNotNull($data);
        
        $this->await($statement->close());
        
        // TODO: maybe remove if statement test succeeds?
        // Unfortunately the destructor mechanism CAN NOT be tested,
        // as the destructor runs AFTER the test ends
    }
    
    function testExecute() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306).'/information_schema');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $client
            ->expects($this->once())
            ->method('checkinConnection')
            ->with($driver);
        
        $prom = $driver->execute($client, 'SELECT * FROM `SCHEMATA` WHERE `SCHEMA_NAME` = ?', array('plasma_tmp'));
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $prom);
        
        $res = $this->await($prom);
        $this->assertInstanceOf(\Plasma\StreamQueryResultInterface::class, $res);
        
        $data = null;
        $deferred = new \React\Promise\Deferred();
        
        $res->once('close', function () use (&$deferred) {
            $deferred->resolve();
        });
        
        $res->once('error', function (\Throwable $e) use (&$deferred) {
            $deferred->reject($e);
        });
        
        $res->on('data', function ($row) use (&$data) {
            if($row['SCHEMA_NAME'] === 'plasma_tmp') {
                $data = $row;
            }
        });
        
        $this->await($deferred->promise());
        $this->assertNotNull($data);
        
        // Waiting 2 seconds for the automatic close to occurr
        $deferredT = new \React\Promise\Deferred();
        $this->loop->addTimer(2, array($deferredT, 'resolve'));
        
        $this->await($deferredT->promise());
    }
    
    function testGoingAwayConnect() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $this->assertNull($driver->quit());
        
        $connect = $driver->connect('whatever');
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $connect);
        
        $this->expectException(\Plasma\Exception::class);
        $this->expectExceptionMessage('Connection is going away');
        
        $this->await($connect, 0.1);
    }
    
    function testGoingAwayStreamConsumption() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $this->assertFalse($driver->resumeStreamConsumption());
        $this->assertFalse($driver->pauseStreamConsumption());
        
        $this->assertNull($driver->quit());
        
        $this->assertFalse($driver->resumeStreamConsumption());
        $this->assertFalse($driver->pauseStreamConsumption());
    }
    
    function testGoingAwayClose() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $this->assertNull($driver->quit());
        
        $close = $driver->close();
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $close);
        
        $this->await($close, 0.1);
    }
    
    function testGoingAwayQuit() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $this->assertNull($driver->quit());
        $this->assertNull($driver->quit());
    }
    
    function testGoingAwayQuery() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $this->assertNull($driver->quit());
        $client = $this->createClientMock();
        
        $query = $driver->query($client, 'whatever');
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $query);
        
        $this->expectException(\Plasma\Exception::class);
        $this->expectExceptionMessage('Connection is going away');
        
        $this->await($query, 0.1);
    }
    
    function testGoingAwayPrepare() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $this->assertNull($driver->quit());
        $client = $this->createClientMock();
        
        $query = $driver->prepare($client, 'whatever');
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $query);
        
        $this->expectException(\Plasma\Exception::class);
        $this->expectExceptionMessage('Connection is going away');
        
        $this->await($query, 0.1);
    }
    
    function testGoingAwayExecute() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $this->assertNull($driver->quit());
        $client = $this->createClientMock();
        
        $query = $driver->execute($client, 'whatever');
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $query);
        
        $this->expectException(\Plasma\Exception::class);
        $this->expectExceptionMessage('Connection is going away');
        
        $this->await($query, 0.1);
    }
    
    function testGoingAwayBeginTransaction() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $this->assertNull($driver->quit());
        $client = $this->createClientMock();
        
        $query = $driver->beginTransaction($client, \Plasma\TransactionInterface::ISOLATION_COMMITTED);
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $query);
        
        $this->expectException(\Plasma\Exception::class);
        $this->expectExceptionMessage('Connection is going away');
        
        $this->await($query, 0.1);
    }
    
    function testGoingAwayRunCommand() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $this->assertNull($driver->quit());
        
        $cmd = new \Plasma\Drivers\MySQL\Commands\QuitCommand();
        $client = $this->createClientMock();
        
        $command = $driver->runCommand($client, $cmd);
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $command);
        
        $this->expectException(\Plasma\Exception::class);
        $this->expectExceptionMessage('Connection is going away');
        
        $this->await($command, 0.1);
    }
    
    function testUnconnectedQuery() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $this->expectException(\Plasma\Exception::class);
        $this->expectExceptionMessage('Unable to continue without connection');
        
        $client = $this->createClientMock();
        $query = $driver->query($client, 'whatever');
    }
    
    function testUnconnectedPrepare() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $this->expectException(\Plasma\Exception::class);
        $this->expectExceptionMessage('Unable to continue without connection');
        
        $client = $this->createClientMock();
        $query = $driver->prepare($client, 'whatever');
    }
    
    function testUnconnectedExecute() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $this->expectException(\Plasma\Exception::class);
        $this->expectExceptionMessage('Unable to continue without connection');
        
        $client = $this->createClientMock();
        $query = $driver->execute($client, 'whatever');
    }
    
    function testQuote() {
        $driver = new \Plasma\Drivers\MySQL\Driver($this->loop, array('characters.set' => ''));
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost');
        $this->await($prom);
        
        $str = $driver->quote('hello "world"');
        $this->assertContains($str, array(
            '"hello \"world\""',
            '"hello ""world"""'
        ));
    }
    
    function testQuoteIdentifier() {
        $driver = new \Plasma\Drivers\MySQL\Driver($this->loop, array('characters.set' => ''));
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost');
        $this->await($prom);
        
        $str = $driver->quote('hello `world`', \Plasma\DriverInterface::QUOTE_TYPE_IDENTIFIER);
        $this->assertContains($str, array(
            '`hello \`world\``',
            '`hello ``world```'
        ));
    }
    
    function testQuoteWithOkResponse() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost');
        $this->await($prom);
        
        $str = $driver->quote('hello "world"');
        $this->assertContains($str, array(
            '"hello \"world\""',
            '"hello ""world"""'
        ));
    }
    
    function testQuoteWithOkResponseIdentifier() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost');
        $this->await($prom);
        
        $str = $driver->quote('hello `world`', \Plasma\DriverInterface::QUOTE_TYPE_IDENTIFIER);
        $this->assertContains($str, array(
            '`hello \`world\``',
            '`hello ``world```'
        ));
    }
    
    function testQuoteWithoutConnection() {
        $driver = new \Plasma\Drivers\MySQL\Driver($this->loop, array('characters.set' => ''));
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $this->expectException(\Plasma\Exception::class);
        $this->expectExceptionMessage('Unable to continue without connection');
        
        $str = $driver->quote('hello "world"');
    }
    
    function testQuoteQuotes() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $str = $driver->escapeUsingQuotes('UTF-8', 'hello "world"', \Plasma\DriverInterface::QUOTE_TYPE_VALUE);
        $this->assertSame('"hello ""world"""', $str);
    }
    
    function testQuoteBackslashes() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $str = $driver->escapeUsingBackslashes('UTF-8', 'hello "world"', \Plasma\DriverInterface::QUOTE_TYPE_VALUE);
        $this->assertSame('"hello \"world\""', $str);
    }
    
    function testQuoteIdentifierQuotes() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $str = $driver->escapeUsingQuotes('UTF-8', 'hello`_world', \Plasma\DriverInterface::QUOTE_TYPE_IDENTIFIER);
        $this->assertSame('`hello``_world`', $str);
    }
    
    function testQuoteIdentifierBackslashes() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $str = $driver->escapeUsingBackslashes('UTF-8', 'hello`_world', \Plasma\DriverInterface::QUOTE_TYPE_IDENTIFIER);
        $this->assertSame('`hello\`_world`', $str);
    }
    
    function testTextTypeString() {
        $values = array();
        
        for($i = 0; $i < 18; $i++) {
            $values[] = '""';
        }
        
        $values[1] = '"hello_world"';
        
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost/plasma_tmp');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $this->await($driver->query($client, "SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'"));
        
        $prep = $driver->query($client, 'INSERT INTO `test_strings` VALUES ('.\implode(', ', $values).')');
        $result = $this->await($prep);
        
        $this->assertSame(1, $result->getAffectedRows());
        
        $selprep = $driver->query($client, 'SELECT * FROM `test_strings`');
        $select = $this->await($selprep);
        
        $dataProm = \React\Promise\Stream\first($select);
        $data = $this->await($dataProm);
        
        $this->await($driver->query($client, 'TRUNCATE TABLE `test_strings`'));
        
        $this->assertSame(array(
            'testcol1' => '',
            'testcol2' => 'hello_world',
            'testcol3' => '',
            'testcol4' => '',
            'testcol5' => '',
            'testcol6' => '',
            'testcol7' => "\0\0\0",
            'testcol8' => '',
            'testcol9' => '',
            'testcol10' => '',
            'testcol11' => '',
            'testcol12' => '',
            'testcol13' => '',
            'testcol14' => '',
            'testcol15' => '',
            'testcol16' => "\0",
            'testcol17' => '0.0',
            'testcol18' => ''
        ), $data);
    }
    
    function testTextTypeInt() {
        $values = array(0, 0, 0, 2780, 0, 0);
        
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost/plasma_tmp');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $this->await($driver->query($client, "SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'"));
        
        $prep = $driver->query($client, 'INSERT INTO `test_ints` VALUES ('.\implode(', ', $values).')');
        $result = $this->await($prep);
        
        $this->assertSame(1, $result->getAffectedRows());
        
        $selprep = $driver->query($client, 'SELECT * FROM `test_ints`');
        $select = $this->await($selprep);
        
        $dataProm = \React\Promise\Stream\first($select);
        $data = $this->await($dataProm);
        
        $this->await($driver->query($client, 'TRUNCATE TABLE `test_ints`'));
        
        $this->assertSame(array(
            'testcol1' => '00000',
            'testcol2' => 0,
            'testcol3' => '0000',
            'testcol4' => 2780,
            'testcol5' => 0,
            'testcol6' => 0
        ), $data);
    }
    
    function testTextTypeFloat() {
        $values = array(0.9, 4.3);
        
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost/plasma_tmp');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $this->await($driver->query($client, "SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'"));
        
        $prep = $driver->query($client, 'INSERT INTO `test_floats` VALUES ('.\implode(', ', $values).')');
        $result = $this->await($prep);
        
        $this->assertSame(1, $result->getAffectedRows());
        
        $selprep = $driver->query($client, 'SELECT * FROM `test_floats`');
        $select = $this->await($selprep);
        
        $dataProm = \React\Promise\Stream\first($select);
        $data = $this->await($dataProm);
        
        $this->await($driver->query($client, 'TRUNCATE TABLE `test_floats`'));
        
        // Round single precision float to 1 decimal
        $data['testcol1'] = \round($data['testcol1'], 1);
        
        $this->assertSame(array(
            'testcol1' => 0.9,
            'testcol2' => 4.3
        ), $data);
    }
    
    function testTextTypeDate() {
        $values = array('"2011-03-05"', '"2011-03-05 00:00:00"', '"23:41:03"', 'null');
        
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost/plasma_tmp');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $this->await($driver->query($client, "SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'"));
        
        $prep = $driver->query($client, 'INSERT INTO `test_dates` VALUES ('.\implode(', ', $values).')');
        $result = $this->await($prep);
        
        $this->assertSame(1, $result->getAffectedRows());
        
        $selprep = $driver->query($client, 'SELECT * FROM `test_dates`');
        $select = $this->await($selprep);
        
        $dataProm = \React\Promise\Stream\first($select);
        $data = $this->await($dataProm);
        
        $this->await($driver->query($client, 'TRUNCATE TABLE `test_dates`'));
        
        $timestamp = \time();
        $ts = \DateTime::createFromFormat('Y-m-d H:i:s', $data['testcol4'])->getTimestamp();
        unset($data['testcol4']);
        
        $this->assertSame(array(
            'testcol1' => '2011-03-05',
            'testcol2' => '2011-03-05 00:00:00',
            'testcol3' => '23:41:03'
        ), $data);
        
        // We're happy if we're +/- 1 minute correct
        $this->assertLessThanOrEqual(($timestamp + 60), $ts);
        $this->assertGreaterThanOrEqual(($timestamp - 60), $ts);
    }
    
    function testUnsupportedTypeForBindingParameters() {
        $values = array();
        
        for($i = 0; $i < 18; $i++) {
            $values[] = '';
        }
        
        $values[0] = array('hello', 'world');
        
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost/plasma_tmp');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $this->await($driver->query($client, "SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'"));
        
        $prep = $driver->execute(
            $client,
            'INSERT INTO `test_strings` VALUES ('.\implode(', ', \array_fill(0, 18, '?')).')',
            $values
        );
        
        $this->expectException(\Plasma\Exception::class);
        $this->expectExceptionMessage('Unexpected type for binding parameter: array');
        
        $this->await($prep);
    }
    
    function insertIntoTestString(int $colnum, string $value): array {
        $values = array();
        
        for($i = 0; $i < 18; $i++) {
            if($colnum === $i) {
                $values[] = $value;
            } else {
                $values[] = '';
            }
        }
        
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost/plasma_tmp');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $this->await($driver->query($client, "SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'"));
        
        $prep = $driver->execute(
            $client,
            'INSERT INTO `test_strings` VALUES ('.\implode(', ', \array_fill(0, 18, '?')).')',
            $values
        );
        $result = $this->await($prep);
        
        $this->assertSame(1, $result->getAffectedRows());
        
        $selprep = $driver->execute($client, 'SELECT * FROM `test_strings`');
        $select = $this->await($selprep);
        
        $dataProm = \React\Promise\Stream\first($select);
        $data = $this->await($dataProm);
        
        $this->await($driver->query($client, 'TRUNCATE TABLE `test_strings`'));
        return $data;
    }
    
    function testBinaryTypeChar() {
        $data = $this->insertIntoTestString(0, 'hell');
        
        $this->assertSame(array(
            'testcol1' => 'hell',
            'testcol2' => '',
            'testcol3' => '',
            'testcol4' => '',
            'testcol5' => '',
            'testcol6' => '',
            'testcol7' => "\0\0\0",
            'testcol8' => '',
            'testcol9' => '',
            'testcol10' => '',
            'testcol11' => '',
            'testcol12' => '',
            'testcol13' => '',
            'testcol14' => '',
            'testcol15' => '',
            'testcol16' => "\0",
            'testcol17' => '0.0',
            'testcol18' => ''
        ), $data);
    }
    
    function testBinaryTypeVarchar() {
        $data = $this->insertIntoTestString(1, 'hello');
        
        $this->assertSame(array(
            'testcol1' => '',
            'testcol2' => 'hello',
            'testcol3' => '',
            'testcol4' => '',
            'testcol5' => '',
            'testcol6' => '',
            'testcol7' => "\0\0\0",
            'testcol8' => '',
            'testcol9' => '',
            'testcol10' => '',
            'testcol11' => '',
            'testcol12' => '',
            'testcol13' => '',
            'testcol14' => '',
            'testcol15' => '',
            'testcol16' => "\0",
            'testcol17' => '0.0',
            'testcol18' => ''
        ), $data);
    }
    
    function testBinaryTypeTinyText() {
        $data = $this->insertIntoTestString(2, 'hallo');
        
        $this->assertSame(array(
            'testcol1' => '',
            'testcol2' => '',
            'testcol3' => 'hallo',
            'testcol4' => '',
            'testcol5' => '',
            'testcol6' => '',
            'testcol7' => "\0\0\0",
            'testcol8' => '',
            'testcol9' => '',
            'testcol10' => '',
            'testcol11' => '',
            'testcol12' => '',
            'testcol13' => '',
            'testcol14' => '',
            'testcol15' => '',
            'testcol16' => "\0",
            'testcol17' => '0.0',
            'testcol18' => ''
        ), $data);
    }
    
    function testBinaryTypeText() {
        $data = $this->insertIntoTestString(3, 'hallo2');
        
        $this->assertSame(array(
            'testcol1' => '',
            'testcol2' => '',
            'testcol3' => '',
            'testcol4' => 'hallo2',
            'testcol5' => '',
            'testcol6' => '',
            'testcol7' => "\0\0\0",
            'testcol8' => '',
            'testcol9' => '',
            'testcol10' => '',
            'testcol11' => '',
            'testcol12' => '',
            'testcol13' => '',
            'testcol14' => '',
            'testcol15' => '',
            'testcol16' => "\0",
            'testcol17' => '0.0',
            'testcol18' => ''
        ), $data);
    }
    
    function testBinaryTypeMediumText() {
        $data = $this->insertIntoTestString(4, 'hallo3');
        
        $this->assertSame(array(
            'testcol1' => '',
            'testcol2' => '',
            'testcol3' => '',
            'testcol4' => '',
            'testcol5' => 'hallo3',
            'testcol6' => '',
            'testcol7' => "\0\0\0",
            'testcol8' => '',
            'testcol9' => '',
            'testcol10' => '',
            'testcol11' => '',
            'testcol12' => '',
            'testcol13' => '',
            'testcol14' => '',
            'testcol15' => '',
            'testcol16' => "\0",
            'testcol17' => '0.0',
            'testcol18' => ''
        ), $data);
    }
    
    function testBinaryTypeLongText() {
        $data = $this->insertIntoTestString(5, 'hallo4');
        
        $this->assertSame(array(
            'testcol1' => '',
            'testcol2' => '',
            'testcol3' => '',
            'testcol4' => '',
            'testcol5' => '',
            'testcol6' => 'hallo4',
            'testcol7' => "\0\0\0",
            'testcol8' => '',
            'testcol9' => '',
            'testcol10' => '',
            'testcol11' => '',
            'testcol12' => '',
            'testcol13' => '',
            'testcol14' => '',
            'testcol15' => '',
            'testcol16' => "\0",
            'testcol17' => '0.0',
            'testcol18' => ''
        ), $data);
    }
    
    function testBinaryTypeBinary() {
        $data = $this->insertIntoTestString(6, "\1\1\0");
        
        $this->assertSame(array(
            'testcol1' => '',
            'testcol2' => '',
            'testcol3' => '',
            'testcol4' => '',
            'testcol5' => '',
            'testcol6' => '',
            'testcol7' => "\1\1\0",
            'testcol8' => '',
            'testcol9' => '',
            'testcol10' => '',
            'testcol11' => '',
            'testcol12' => '',
            'testcol13' => '',
            'testcol14' => '',
            'testcol15' => '',
            'testcol16' => "\0",
            'testcol17' => '0.0',
            'testcol18' => ''
        ), $data);
    }
    
    function testBinaryTypeVarBinary() {
        $data = $this->insertIntoTestString(7, 'hallo6');
        
        $this->assertSame(array(
            'testcol1' => '',
            'testcol2' => '',
            'testcol3' => '',
            'testcol4' => '',
            'testcol5' => '',
            'testcol6' => '',
            'testcol7' => "\0\0\0",
            'testcol8' => 'hallo6',
            'testcol9' => '',
            'testcol10' => '',
            'testcol11' => '',
            'testcol12' => '',
            'testcol13' => '',
            'testcol14' => '',
            'testcol15' => '',
            'testcol16' => "\0",
            'testcol17' => '0.0',
            'testcol18' => ''
        ), $data);
    }
    
    function testBinaryTypeTinyBlob() {
        $data = $this->insertIntoTestString(8, 'hallo7');
        
        $this->assertSame(array(
            'testcol1' => '',
            'testcol2' => '',
            'testcol3' => '',
            'testcol4' => '',
            'testcol5' => '',
            'testcol6' => '',
            'testcol7' => "\0\0\0",
            'testcol8' => '',
            'testcol9' => 'hallo7',
            'testcol10' => '',
            'testcol11' => '',
            'testcol12' => '',
            'testcol13' => '',
            'testcol14' => '',
            'testcol15' => '',
            'testcol16' => "\0",
            'testcol17' => '0.0',
            'testcol18' => ''
        ), $data);
    }
    
    function testBinaryTypeMediumBlob() {
        $data = $this->insertIntoTestString(9, 'hallo8');
        
        $this->assertSame(array(
            'testcol1' => '',
            'testcol2' => '',
            'testcol3' => '',
            'testcol4' => '',
            'testcol5' => '',
            'testcol6' => '',
            'testcol7' => "\0\0\0",
            'testcol8' => '',
            'testcol9' => '',
            'testcol10' => 'hallo8',
            'testcol11' => '',
            'testcol12' => '',
            'testcol13' => '',
            'testcol14' => '',
            'testcol15' => '',
            'testcol16' => "\0",
            'testcol17' => '0.0',
            'testcol18' => ''
        ), $data);
    }
    
    function testBinaryTypeBlob() {
        $data = $this->insertIntoTestString(10, 'hallo9');
        
        $this->assertSame(array(
            'testcol1' => '',
            'testcol2' => '',
            'testcol3' => '',
            'testcol4' => '',
            'testcol5' => '',
            'testcol6' => '',
            'testcol7' => "\0\0\0",
            'testcol8' => '',
            'testcol9' => '',
            'testcol10' => '',
            'testcol11' => 'hallo9',
            'testcol12' => '',
            'testcol13' => '',
            'testcol14' => '',
            'testcol15' => '',
            'testcol16' => "\0",
            'testcol17' => '0.0',
            'testcol18' => ''
        ), $data);
    }
    
    function testBinaryTypeLongBlob() {
        $data = $this->insertIntoTestString(11, 'hello world');
        
        $this->assertSame(array(
            'testcol1' => '',
            'testcol2' => '',
            'testcol3' => '',
            'testcol4' => '',
            'testcol5' => '',
            'testcol6' => '',
            'testcol7' => "\0\0\0",
            'testcol8' => '',
            'testcol9' => '',
            'testcol10' => '',
            'testcol11' => '',
            'testcol12' => 'hello world',
            'testcol13' => '',
            'testcol14' => '',
            'testcol15' => '',
            'testcol16' => "\0",
            'testcol17' => '0.0',
            'testcol18' => ''
        ), $data);
    }
    
    function testBinaryTypeEnum() {
        $data = $this->insertIntoTestString(12, 'hey');
        
        $this->assertSame(array(
            'testcol1' => '',
            'testcol2' => '',
            'testcol3' => '',
            'testcol4' => '',
            'testcol5' => '',
            'testcol6' => '',
            'testcol7' => "\0\0\0",
            'testcol8' => '',
            'testcol9' => '',
            'testcol10' => '',
            'testcol11' => '',
            'testcol12' => '',
            'testcol13' => 'hey',
            'testcol14' => '',
            'testcol15' => '',
            'testcol16' => "\0",
            'testcol17' => '0.0',
            'testcol18' => ''
        ), $data);
    }
    
    function testBinaryTypeSet() {
        $data = $this->insertIntoTestString(13, 'world');
        
        $this->assertSame(array(
            'testcol1' => '',
            'testcol2' => '',
            'testcol3' => '',
            'testcol4' => '',
            'testcol5' => '',
            'testcol6' => '',
            'testcol7' => "\0\0\0",
            'testcol8' => '',
            'testcol9' => '',
            'testcol10' => '',
            'testcol11' => '',
            'testcol12' => '',
            'testcol13' => '',
            'testcol14' => 'world',
            'testcol15' => '',
            'testcol16' => "\0",
            'testcol17' => '0.0',
            'testcol18' => ''
        ), $data);
    }
    
    /* Roughly supported since MySQL 5.7
    function testBinaryTypeGeometry() {
        $data = $this->insertIntoTestString(14, '');
        
        $this->assertSame(array(
            'testcol1' => '',
            'testcol2' => '',
            'testcol3' => '',
            'testcol4' => '',
            'testcol5' => '',
            'testcol6' => '',
            'testcol7' => "\0\0\0",
            'testcol8' => '',
            'testcol9' => '',
            'testcol10' => '',
            'testcol11' => '',
            'testcol12' => '',
            'testcol13' => '',
            'testcol14' => '',
            'testcol15' => '',
            'testcol16' => "\0",
            'testcol17' => '0.0',
            'testcol18' => ''
        ), $data);
    }*/
    
    function testBinaryTypeBit() {
        $data = $this->insertIntoTestString(15, "\1");
        
        $this->assertSame(array(
            'testcol1' => '',
            'testcol2' => '',
            'testcol3' => '',
            'testcol4' => '',
            'testcol5' => '',
            'testcol6' => '',
            'testcol7' => "\0\0\0",
            'testcol8' => '',
            'testcol9' => '',
            'testcol10' => '',
            'testcol11' => '',
            'testcol12' => '',
            'testcol13' => '',
            'testcol14' => '',
            'testcol15' => '',
            'testcol16' => "\1",
            'testcol17' => '0.0',
            'testcol18' => ''
        ), $data);
    }
    
    function testBinaryTypeDecimal() {
        $data = $this->insertIntoTestString(16, '5.2');
        
        $this->assertSame(array(
            'testcol1' => '',
            'testcol2' => '',
            'testcol3' => '',
            'testcol4' => '',
            'testcol5' => '',
            'testcol6' => '',
            'testcol7' => "\0\0\0",
            'testcol8' => '',
            'testcol9' => '',
            'testcol10' => '',
            'testcol11' => '',
            'testcol12' => '',
            'testcol13' => '',
            'testcol14' => '',
            'testcol15' => '',
            'testcol16' => "\0",
            'testcol17' => '5.2',
            'testcol18' => ''
        ), $data);
    }
    
    /* JSON is supported since roughly MySQL 8.0
    function testBinaryTypeNewJSON() {
        $data = $this->insertIntoTestString(18, '{"hello":true}');
        
        $this->assertSame(array(
            'testcol1' => '',
            'testcol2' => '',
            'testcol3' => '',
            'testcol4' => '',
            'testcol5' => '',
            'testcol6' => '',
            'testcol7' => "\0\0\0",
            'testcol8' => '',
            'testcol9' => '',
            'testcol10' => '',
            'testcol11' => '',
            'testcol12' => '',
            'testcol13' => '',
            'testcol14' => '',
            'testcol15' => '',
            'testcol16' => "\0",
            'testcol17' => '0.0',
            'testcol18' => '{"hello":true}'
        ), $data);
    }*/
    
    function insertIntoTestInt(int $colnum, int $value): array {
        $values = array();
        
        for($i = 0; $i < 6; $i++) {
            if($colnum === $i) {
                $values[] = $value;
            } else {
                $values[] = 0;
            }
        }
        
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost/plasma_tmp');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $this->await($driver->query($client, "SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'"));
        
        $prep = $driver->execute(
            $client,
            'INSERT INTO `test_ints` VALUES ('.\implode(', ', \array_fill(0, 6, '?')).')',
            $values
        );
        $result = $this->await($prep);
        
        $this->assertSame(1, $result->getAffectedRows());
        
        $selprep = $driver->execute($client, 'SELECT * FROM `test_ints`');
        $select = $this->await($selprep);
        
        $dataProm = \React\Promise\Stream\first($select);
        $data = $this->await($dataProm);
        
        $this->await($driver->query($client, 'TRUNCATE TABLE `test_ints`'));
        return $data;
    }
    
    function testBinaryTypeTinyBoolean() {
        $values = array(true, 0, 0, 0, 0, 0);
        
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost/plasma_tmp');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $this->await($driver->query($client, "SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'"));
        
        $prep = $driver->execute(
            $client,
            'INSERT INTO `test_ints` VALUES ('.\implode(', ', \array_fill(0, 6, '?')).')',
            $values
        );
        $result = $this->await($prep);
        
        $this->assertSame(1, $result->getAffectedRows());
        
        $selprep = $driver->execute($client, 'SELECT * FROM `test_ints`');
        $select = $this->await($selprep);
        
        $dataProm = \React\Promise\Stream\first($select);
        $data = $this->await($dataProm);
        
        $this->await($driver->query($client, 'TRUNCATE TABLE `test_ints`'));
        
        $this->assertSame(array(
            'testcol1' => '00001',
            'testcol2' => 0,
            'testcol3' => '0000',
            'testcol4' => 0,
            'testcol5' => 0,
            'testcol6' => 0
        ), $data);
    }
    
    function testBinaryTypeTiny() {
        $data = $this->insertIntoTestInt(0, 5);
        
        $this->assertSame(array(
            'testcol1' => '00005',
            'testcol2' => 0,
            'testcol3' => '0000',
            'testcol4' => 0,
            'testcol5' => 0,
            'testcol6' => 0
        ), $data);
    }
    
    function testBinaryTypeShort() {
        $data = $this->insertIntoTestInt(1, 32040);
        
        $this->assertSame(array(
            'testcol1' => '00000',
            'testcol2' => 32040,
            'testcol3' => '0000',
            'testcol4' => 0,
            'testcol5' => 0,
            'testcol6' => 0
        ), $data);
    }
    
    function testBinaryTypeYear() {
        $data = $this->insertIntoTestInt(2, 2014);
        
        $this->assertSame(array(
            'testcol1' => '00000',
            'testcol2' => 0,
            'testcol3' => '2014',
            'testcol4' => 0,
            'testcol5' => 0,
            'testcol6' => 0
        ), $data);
    }
    
    function testBinaryTypeInt24() {
        $data = $this->insertIntoTestInt(3, 1677416);
        
        $this->assertSame(array(
            'testcol1' => '00000',
            'testcol2' => 0,
            'testcol3' => '0000',
            'testcol4' => 1677416,
            'testcol5' => 0,
            'testcol6' => 0
        ), $data);
    }
    
    function testBinaryTypeLong() {
        $data = $this->insertIntoTestInt(4, 1147283648);
        
        $this->assertSame(array(
            'testcol1' => '00000',
            'testcol2' => 0,
            'testcol3' => '0000',
            'testcol4' => 0,
            'testcol5' => 1147283648,
            'testcol6' => 0
        ), $data);
    }
    
    function testBinaryTypeLongLong() {
        $data = $this->insertIntoTestInt(5, 261168601842738);
        
        $this->assertSame(array(
            'testcol1' => '00000',
            'testcol2' => 0,
            'testcol3' => '0000',
            'testcol4' => 0,
            'testcol5' => 0,
            'testcol6' => 261168601842738
        ), $data);
    }
    
    function insertIntoTestFloat(int $colnum, float $value): array {
        $values = array();
        
        for($i = 0; $i < 2; $i++) {
            if($colnum === $i) {
                $values[] = $value;
            } else {
                $values[] = 0.0;
            }
        }
        
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost/plasma_tmp');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $this->await($driver->query($client, "SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'"));
        
        $prep = $driver->execute(
            $client,
            'INSERT INTO `test_floats` VALUES (?, ?)',
            $values
        );
        $result = $this->await($prep);
        
        $this->assertSame(1, $result->getAffectedRows());
        
        $selprep = $driver->execute($client, 'SELECT * FROM `test_floats`');
        $select = $this->await($selprep);
        
        $dataProm = \React\Promise\Stream\first($select);
        $data = $this->await($dataProm);
        
        $this->await($driver->query($client, 'TRUNCATE TABLE `test_floats`'));
        return $data;
    }
    
    function testBinaryTypeFloat() {
        $data = $this->insertIntoTestFloat(0, 5.2);
        $this->assertSame(5.2, \round($data['testcol1'], 1));
    }
    
    function testBinaryTypeDouble() {
        $data = $this->insertIntoTestFloat(1, 25.2543543143);
        $this->assertSame(25.25, \round($data['testcol2'], 2));
    }
    
    function insertIntoTestDate(int $colnum, $value): array {
        $values = array();
        
        for($i = 0; $i < 4; $i++) {
            if($colnum === $i) {
                $values[] = ($colnum === 3 && $value === '0' ? null : $value);
            } else {
                $values[] = '';
            }
        }
        
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost/plasma_tmp');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $this->await($driver->query($client, "SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'"));
        
        $prep = $driver->execute(
            $client,
            'INSERT INTO `test_dates` VALUES (?, ?, ?, ?)',
            $values
        );
        $result = $this->await($prep);
        
        $this->assertSame(1, $result->getAffectedRows());
        
        $selprep = $driver->execute($client, 'SELECT * FROM `test_dates`');
        $select = $this->await($selprep);
        
        $dataProm = \React\Promise\Stream\first($select);
        $data = $this->await($dataProm);
        
        $this->await($driver->query($client, 'TRUNCATE TABLE `test_dates`'));
        return $data;
    }
    
    function testBinaryTypeDate() {
        $data = $this->insertIntoTestDate(0, '2011-03-05');
        
        $this->assertSame(array(
            'testcol1' => '2011-03-05',
            'testcol2' => '0000-00-00 00:00:00.000 000',
            'testcol3' => '0d 00:00:00',
            'testcol4' => '0000-00-00 00:00:00.000 000'
        ), $data);
    }
    
    function testBinaryTypeDateZero() {
        $data = $this->insertIntoTestDate(0, '0000-00-00');
        
        $this->assertSame(array(
            'testcol1' => '0000-00-00',
            'testcol2' => '0000-00-00 00:00:00.000 000',
            'testcol3' => '0d 00:00:00',
            'testcol4' => '0000-00-00 00:00:00.000 000'
        ), $data);
    }
    
    function testBinaryTypeDateTime() {
        $data = $this->insertIntoTestDate(1, '2011-03-05 00:00:00.000 000');
        
        $this->assertSame(array(
            'testcol1' => '0000-00-00',
            'testcol2' => '2011-03-05 00:00:00',
            'testcol3' => '0d 00:00:00',
            'testcol4' => '0000-00-00 00:00:00.000 000'
        ), $data);
    }
    
    /* Inserting micros does not work
    function testBinaryTypeDateTimeMicros() {
        $data = $this->insertIntoTestDate(1, '2011-03-05 21:05:30.050 000');
        
        $this->assertSame(array(
            'testcol1' => '0000-00-00',
            'testcol2' => '2011-03-05 21:05:30.050 000',
            'testcol3' => '0d 00:00:00',
            'testcol4' => '0000-00-00 00:00:00.000 000'
        ), $data);
    }*/
    
    function testBinaryTypeDateTimeZeroed() {
        $data = $this->insertIntoTestDate(1, '0000-00-00 00:00:00.000 000');
        
        $this->assertSame(array(
            'testcol1' => '0000-00-00',
            'testcol2' => '0000-00-00 00:00:00.000 000',
            'testcol3' => '0d 00:00:00',
            'testcol4' => '0000-00-00 00:00:00.000 000'
        ), $data);
    }
    
    function testBinaryTypeTime() {
        $data = $this->insertIntoTestDate(2, '23:41:03.000 000');
        
        $this->assertSame(array(
            'testcol1' => '0000-00-00',
            'testcol2' => '0000-00-00 00:00:00.000 000',
            'testcol3' => '0d 23:41:03',
            'testcol4' => '0000-00-00 00:00:00.000 000'
        ), $data);
    }
    
    /* Inserting days or micros does not work
    function testBinaryTypeTimeMicros() {
        $data = $this->insertIntoTestDate(2, '12d 23:41:03.410 000');
        
        $this->assertSame(array(
            'testcol1' => '0000-00-00',
            'testcol2' => '0000-00-00 00:00:00.000 000',
            'testcol3' => '12d 23:41:03.410 000',
            'testcol4' => '0000-00-00 00:00:00.000 000'
        ), $data);
    }*/
    
    function testBinaryTypeTimeZeroed() {
        $data = $this->insertIntoTestDate(2, '0d 00:00:00');
        
        $this->assertSame(array(
            'testcol1' => '0000-00-00',
            'testcol2' => '0000-00-00 00:00:00.000 000',
            'testcol3' => '0d 00:00:00',
            'testcol4' => '0000-00-00 00:00:00.000 000'
        ), $data);
    }
    
    function testBinaryTypeTimestamp() {
        // We don't actually insert a timestamp
        // instead the DBMS inserts the current timestamp
        $data = $this->insertIntoTestDate(3, '0');
        $timestamp = \time();
        
        $ts = $data['testcol4'];
        unset($data['testcol4']);
        
        $this->assertSame(array(
            'testcol1' => '0000-00-00',
            'testcol2' => '0000-00-00 00:00:00.000 000',
            'testcol3' => '0d 00:00:00'
        ), $data);
        
        // We're happy if we're +/- 1 minute correct
        $this->assertLessThanOrEqual(($timestamp + 60), $ts);
        $this->assertGreaterThanOrEqual(($timestamp - 60), $ts);
    }
    
    function testBinaryTypeTimestampZeroed() {
        $data = $this->insertIntoTestDate(3, '0000-00-00 00:00:00');
        
        $this->assertSame(array(
            'testcol1' => '0000-00-00',
            'testcol2' => '0000-00-00 00:00:00.000 000',
            'testcol3' => '0d 00:00:00',
            'testcol4' => '0000-00-00 00:00:00.000 000'
        ), $data);
    }
    
    function createClientMock(): \Plasma\ClientInterface {
        return $this->getMockBuilder(\Plasma\ClientInterface::class)
            ->setMethods(array(
                'create',
                'getConnectionCount',
                'checkinConnection',
                'beginTransaction',
                'close',
                'quit',
                'runCommand',
                'query',
                'prepare',
                'execute',
                'quote',
                'listeners',
                'on',
                'once',
                'emit',
                'removeListener',
                'removeAllListeners'
            ))
            ->getMock();
    }
}
