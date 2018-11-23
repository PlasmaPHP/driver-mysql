<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018 PlasmaPHP, All Rights Reserved
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
    
    /**
     * @var \Plasma\Drivers\MySQL\Driver
     */
     public $driver;
    
    function setUp() {
        parent::setUp();
        $this->factory = new \Plasma\Drivers\MySQL\DriverFactory($this->loop, array());
    }
    
    function connect(\Plasma\DriverInterface $driver, string $uri): \React\Promise\PromiseInterface {
        $creds = (\getenv('MDB_USER') ? \getenv('MDB_USER').':'.\getenv('MDB_PASSWORD').'@' : 'root:@');
        
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
        
        $prom = $this->connect($driver, 'localhost');
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
    
    function testQuoteWithoutConnection() {
        $driver = new \Plasma\Drivers\MySQL\Driver($this->loop, array('characters.set' => ''));
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost');
        $this->await($prom);
        
        $this->expectException(\Plasma\Exception::class);
        $this->expectExceptionMessage('Unable to continue without connection');
        
        $str = $driver->quote('hello "world"');
    }
    
    function testQuoteQuotes() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $str = $driver->escapeUsingQuotes('UTF-8', 'hello "world"');
        $this->assertSame('"hello ""world"""', $str);
    }
    
    function testQuoteBackslashes() {
        $driver = $this->factory->createDriver();
        $this->assertInstanceOf(\Plasma\DriverInterface::class, $driver);
        
        $str = $driver->escapeUsingBackslashes('UTF-8', 'hello "world"');
        $this->assertSame('"hello \"world\""', $str);
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
