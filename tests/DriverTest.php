<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
 * @noinspection PhpUnhandledExceptionInspection
*/

namespace Plasma\Drivers\MySQL\Tests;

use Plasma\ClientInterface;
use Plasma\DriverInterface;
use Plasma\Drivers\MySQL\Commands\PingCommand;
use Plasma\Drivers\MySQL\Commands\QuitCommand;
use Plasma\Drivers\MySQL\Driver;
use Plasma\Drivers\MySQL\DriverFactory;
use Plasma\Drivers\MySQL\Messages\OkResponseMessage;
use Plasma\Drivers\MySQL\StatementCursor;
use Plasma\Exception;
use Plasma\QueryBuilderInterface;
use Plasma\QueryResultInterface;
use Plasma\SQLQueryBuilderInterface;
use Plasma\StatementInterface;
use Plasma\StreamQueryResultInterface;
use Plasma\TransactionInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use function Clue\React\Block\sleep;
use function React\Promise\all;
use function React\Promise\Stream\first;

class DriverTest extends TestCase {
    /**
     * @var DriverFactory
     */
    public $factory;
    
    function setUp() {
        parent::setUp();
        $this->factory = new DriverFactory($this->loop, array(
            'compression.enable' => false,
            'tls.force' => false,
            'tls.forceLocal' => false
        ));
    }
    
    function connect(DriverInterface $driver, string $uri, string $scheme = 'tcp'): PromiseInterface {
        $creds = (\getenv('MDB_USER') ? \getenv('MDB_USER').':'.\getenv('MDB_PASSWORD').'@' : 'root:@');
        
        return $driver->connect($scheme.'://'.$creds.$uri);
    }
    
    function testGetLoop() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $loop = $driver->getLoop();
        self::assertInstanceOf(LoopInterface::class, $loop);
        self::assertSame($this->loop, $loop);
    }
    
    function testGetConnectionState() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $state = $driver->getConnectionState();
        self::assertSame(DriverInterface::CONNECTION_CLOSED, $state);
    }
    
    function testGetBusyState() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $state = $driver->getBusyState();
        self::assertSame(DriverInterface::STATE_IDLE, $state);
    }
    
    function testGetBacklogLength() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $state = $driver->getBacklogLength();
        self::assertSame(0, $state);
        
        $client = $this->createClientMock();
        
        /** @noinspection PhpUndefinedMethodInspection */
        $client
            ->expects(self::any())
            ->method('checkinConnection')
            ->with($driver);
        
        $ping = new PingCommand();
        $driver->runCommand($client, $ping);
        
        $state = $driver->getBacklogLength();
        self::assertSame(1, $state);
    }
    
    function testConnect() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $deferred = new Deferred();
        $driver->on('eventRelay', function (string $event, $arg) use ($deferred) {
            if($event === 'serverOkMessage') {
                $deferred->resolve($arg);
            }
        });
        
        $prom = $this->connect($driver, 'localhost', 'mysql');
        self::assertInstanceOf(PromiseInterface::class, $prom);
        self::assertSame(DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->await($prom);
        self::assertSame(DriverInterface::CONNECTION_OK, $driver->getConnectionState());
        
        $prom2 = $this->connect($driver, 'localhost');
        self::assertInstanceOf(PromiseInterface::class, $prom2);
        
        $msg = $this->await($deferred->promise(), 0.1);
        self::assertInstanceOf(OkResponseMessage::class, $msg);
    }
    
    function testConnectWithPort() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306));
        self::assertInstanceOf(PromiseInterface::class, $prom);
        self::assertSame(DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $prom2 = $this->connect($driver, 'localhost');
        self::assertSame($prom, $prom2);
        
        $this->await($prom);
        self::assertSame(DriverInterface::CONNECTION_OK, $driver->getConnectionState());
    }
    
    function testConnectUnix() {
        if(\DIRECTORY_SEPARATOR === '\\') {
            /** @noinspection PhpVoidFunctionResultUsedInspection */
            return self::markTestSkipped('Not supported on windows');
        }
        
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, '/var/run/mysqld/mysqld.sock/', 'unix');
        self::assertInstanceOf(PromiseInterface::class, $prom);
        self::assertGreaterThanOrEqual(DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->await($prom);
        self::assertSame(DriverInterface::CONNECTION_OK, $driver->getConnectionState());
    }
    
    function testConnectInvalidUnixHostWithoutUsername() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $this->expectException(\InvalidArgumentException::class);
        $driver->connect('unix://localhost');
    }
    
    function testConnectInvalidCredentials() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $driver->connect('root:abc-never@localhost');
        self::assertInstanceOf(PromiseInterface::class, $prom);
        self::assertSame(DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessageRegExp('/^Access denied for user/i');
        
        $this->await($prom);
    }
    
    function testConnectInvalidHost() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $this->expectException(\InvalidArgumentException::class);
        $driver->connect('');
    }
    
    function testConnectInvalidScheme() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $this->expectException(\InvalidArgumentException::class);
        $driver->connect('dns://localhost');
    }
    
    /**
     * @group tls
     */
    function testConnectForceTLSLocalhost() {
        $factory = new DriverFactory($this->loop, array('compression.enable' => false, 'tls.force' => true, 'tls.forceLocal' => true));
        $driver = $factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT_SECURE') ?: (\getenv('MDB_PORT') ?: 3306)));
        self::assertInstanceOf(PromiseInterface::class, $prom);
        self::assertSame(DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->await($prom);
        self::assertSame(DriverInterface::CONNECTION_OK, $driver->getConnectionState());
    }
    
    /**
     * @group tls
     */
    function testConnectForceTLSLocalhostIgnored() {
        $factory = new DriverFactory($this->loop, array('compression.enable' => false, 'tls.force' => true));
        $driver = $factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306));
        self::assertInstanceOf(PromiseInterface::class, $prom);
        self::assertSame(DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->await($prom);
        self::assertSame(DriverInterface::CONNECTION_OK, $driver->getConnectionState());
    }
    
    /**
     * @group tls
     */
    function testConnectForceTLSIgnoredSecureServer() {
        $factory = new DriverFactory($this->loop, array('compression.enable' => false, 'tls.force' => true));
        $driver = $factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT_SECURE') ?: (\getenv('MDB_PORT') ?: 3306)));
        self::assertInstanceOf(PromiseInterface::class, $prom);
        self::assertSame(DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->await($prom);
        self::assertSame(DriverInterface::CONNECTION_OK, $driver->getConnectionState());
    }
    
    /**
     * @group tls
     */
    function testConnectForceTLSFailure() {
        $factory = new DriverFactory($this->loop, array('compression.enable' => false, 'tls.force' => true, 'tls.forceLocal' => true));
        $driver = $factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306));
        self::assertInstanceOf(PromiseInterface::class, $prom);
        self::assertSame(DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('TLS is not supported by the server');
        
        $this->await($prom, 30.0);
    }
    
    function testDriverCloseDuringMakingConnection() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $driver->connect('mysql://localhost');
        self::assertInstanceOf(PromiseInterface::class, $prom);
        self::assertSame(DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->await($driver->close());
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageRegExp('/Connection to .*? cancelled/i');
        
        $this->await($prom);
    }
    
    function testDriverQuitDuringMakingConnection() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $driver->connect('mysql://localhost');
        self::assertInstanceOf(PromiseInterface::class, $prom);
        self::assertSame(DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $driver->quit();
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageRegExp('/Connection to .*? cancelled/i');
        
        $this->await($prom);
    }
    
    function testClose() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, '127.0.0.1:'.(\getenv('MDB_PORT') ?: 3306));
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        /** @noinspection PhpUndefinedMethodInspection */
        $client
            ->expects(self::exactly(2))
            ->method('checkinConnection')
            ->with($driver);
        
        $ping = new PingCommand();
        $driver->runCommand($client, $ping);
        
        $promC = $driver->runCommand($client, $ping);
        self::assertInstanceOf(PromiseInterface::class, $promC);
        
        $prom2 = $driver->close();
        self::assertInstanceOf(PromiseInterface::class, $prom2);
        
        $this->await($promC);
        $this->await($prom2);
        
        $prom3 = $driver->close();
        self::assertInstanceOf(PromiseInterface::class, $prom3);
    }
    
    function testQuit() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $deferred = new Deferred();
        
        $driver->once('close', function () use (&$deferred) {
            $deferred->resolve();
        });
        
        $prom = $this->connect($driver, '127.0.0.1:'.(\getenv('MDB_PORT') ?: 3306));
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        /** @noinspection PhpUndefinedMethodInspection */
        $client
            ->expects(self::exactly(2))
            ->method('checkinConnection')
            ->with($driver);
        
        $ping = new PingCommand();
        $driver->runCommand($client, $ping);
        
        $promC = $driver->runCommand($client, $ping);
        self::assertInstanceOf(PromiseInterface::class, $promC);
        
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        self::assertNull($driver->quit());
        
        // Command gets rejected due to quit
        try {
            $this->await($promC);
        } catch (\Throwable $e) {
            self::assertInstanceOf(\Throwable::class, $e);
        }
        
        $this->await($deferred->promise());
    }
    
    function testTransaction() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306));
        $this->await($prom);
        
        self::assertFalse($driver->isInTransaction());
        
        $client = $this->createClientMock();
        
        /** @noinspection PhpUndefinedMethodInspection */
        $client
            ->expects(self::once())
            ->method('checkinConnection')
            ->with($driver);
        
        $prom2 = $driver->beginTransaction($client, TransactionInterface::ISOLATION_COMMITTED);
        self::assertInstanceOf(PromiseInterface::class, $prom2);
        
        $transaction = $this->await($prom2);
        self::assertInstanceof(TransactionInterface::class, $transaction);
        
        self::assertTrue($driver->isInTransaction());
        
        $prom3 = $transaction->rollback();
        self::assertInstanceOf(PromiseInterface::class, $prom3);
        
        $this->await($prom3);
        
        self::assertFalse($driver->isInTransaction());
    }
    
    function testAlreadyInTransaction() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306));
        $this->await($prom);
        
        self::assertFalse($driver->isInTransaction());
        
        $client = $this->createClientMock();
        
        /** @noinspection PhpUndefinedMethodInspection */
        $client
            ->expects(self::never())
            ->method('checkinConnection')
            ->with($driver);
        
        $prom2 = $driver->beginTransaction($client, TransactionInterface::ISOLATION_COMMITTED);
        self::assertInstanceOf(PromiseInterface::class, $prom2);
        
        $transaction = $this->await($prom2);
        self::assertInstanceof(TransactionInterface::class, $transaction);
        
        $this->expectException(Exception::class);
        $driver->beginTransaction($client, TransactionInterface::ISOLATION_COMMITTED);
    }
    
    function testRunCommand() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306));
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        /** @noinspection PhpUndefinedMethodInspection */
        $client
            ->expects(self::exactly(2))
            ->method('checkinConnection')
            ->with($driver);
        
        $ping = new PingCommand();
        $promC = $driver->runCommand($client, $ping);
        self::assertInstanceOf(PromiseInterface::class, $promC);
        
        $this->await($promC);
        
        $ping2 = (new class() extends PingCommand {
            function onComplete(): void {
                $this->finished = true;
                $this->emit('error', array((new \LogicException('test'))));
            }
        });
        
        $promC2 = $driver->runCommand($client, $ping2);
        self::assertInstanceOf(PromiseInterface::class, $promC2);
        
        $this->expectException(\LogicException::class);
        $this->await($promC2);
    }
    
    function testRunMultipleCommands() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306));
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        /** @noinspection PhpUndefinedMethodInspection */
        $client
            ->expects(self::exactly(3))
            ->method('checkinConnection')
            ->with($driver);
        
        $ping = new PingCommand();
        $promC = $driver->runCommand($client, $ping);
        self::assertInstanceOf(PromiseInterface::class, $promC);
        
        $ping2 = new PingCommand();
        $promC2 = $driver->runCommand($client, $ping2);
        self::assertInstanceOf(PromiseInterface::class, $promC2);
        
        $ping3 = new PingCommand();
        $promC3 = $driver->runCommand($client, $ping3);
        self::assertInstanceOf(PromiseInterface::class, $promC3);
        
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
        
        $this->await(all(array($promC, $promC2, $promC3)));
        self::assertSame(array(0, 1, 2), $resolval);
    }
    
    function testRunQuery() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306));
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        /** @noinspection PhpUndefinedMethodInspection */
        $client
            ->method('checkinConnection')
            ->with($driver);
        
        $query = $this->getMockBuilder(SQLQueryBuilderInterface::class)
            ->setMethods(array(
                'create',
                'getQuery',
                'getParameters'
            ))
            ->getMock();
        
        $query
            ->expects(self::once())
            ->method('getQuery')
            ->willReturn('SELECT 1');
        
        $query
            ->expects(self::once())
            ->method('getParameters')
            ->willReturn(array());
        
        $prom = $driver->runQuery($client, $query);
        self::assertInstanceOf(PromiseInterface::class, $prom);
        
        $result = $this->await($prom);
        self::assertInstanceOf(QueryResultInterface::class, $result);
    }
    
    function testRunQueryInvalidBuilder() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306));
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $query = $this->getMockBuilder(QueryBuilderInterface::class)
            ->setMethods(array(
                'create',
                'getQuery',
                'getParameters'
            ))
            ->getMock();
        
        $this->expectException(Exception::class);
        $driver->runQuery($client, $query);
    }
    
    function testQuery() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306).'/plasma_tmp');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        /** @noinspection PhpUndefinedMethodInspection */
        $client
            ->expects(self::exactly(2))
            ->method('checkinConnection')
            ->with($driver);
        
        $prom = $driver->query($client, 'CREATE TABLE IF NOT EXISTS `tbl_tmp` (`test` VARCHAR(50) NOT NULL) ENGINE = InnoDB');
        self::assertInstanceOf(PromiseInterface::class, $prom);
        
        $res = $this->await($prom);
        self::assertInstanceOf(QueryResultInterface::class, $res);
        
        $prom2 = $driver->query($client, 'SHOW DATABASES');
        self::assertInstanceOf(PromiseInterface::class, $prom2);
        
        $res2 = $this->await($prom2);
        self::assertInstanceOf(StreamQueryResultInterface::class, $res2);
        
        $data = null;
        $deferred = new Deferred();
        
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
        self::assertSame(array('Database' => 'plasma_tmp'), $data);
    }
    
    function testQuerySelectedDatabase() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306).'/information_schema');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        /** @noinspection PhpUndefinedMethodInspection */
        $client
            ->expects(self::once())
            ->method('checkinConnection')
            ->with($driver);
        
        $prom = $driver->query($client, 'SELECT DATABASE()');
        self::assertInstanceOf(PromiseInterface::class, $prom);
        
        $res = $this->await($prom);
        self::assertInstanceOf(StreamQueryResultInterface::class, $res);
        
        $data = null;
        $deferred = new Deferred();
        
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
        self::assertSame(array('DATABASE()' => 'information_schema'), $data);
    }
    
    function testQueryConnectionCharset() {
        $factory = new DriverFactory($this->loop, array('characters.set' => 'utf8', 'compression.enable' => false));
        
        $driver = $factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306).'/information_schema');
        self::assertSame(DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->await($prom);
        self::assertSame(DriverInterface::CONNECTION_OK, $driver->getConnectionState());
        
        $client = $this->createClientMock();
        
        /** @noinspection PhpUndefinedMethodInspection */
        $client
            ->expects(self::once())
            ->method('checkinConnection')
            ->with($driver);
        
        $prom = $driver->query($client, 'SHOW SESSION VARIABLES LIKE "character\_set\_connection"');
        self::assertInstanceOf(PromiseInterface::class, $prom);
        
        $res = $this->await($prom);
        self::assertInstanceOf(StreamQueryResultInterface::class, $res);
        
        $data = null;
        $deferred = new Deferred();
        
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
        self::assertSame(array('Variable_name' => 'character_set_connection', 'Value' => 'utf8'), $data);
    }
    
    function testQueryConnectionCollate() {
        $factory = new DriverFactory($this->loop, array('characters.collate' => 'utf8mb4_bin', 'compression.enable' => false));
        
        $driver = $factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306).'/information_schema');
        self::assertSame(DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->await($prom);
        self::assertSame(DriverInterface::CONNECTION_OK, $driver->getConnectionState());
        
        $client = $this->createClientMock();
        
        /** @noinspection PhpUndefinedMethodInspection */
        $client
            ->expects(self::once())
            ->method('checkinConnection')
            ->with($driver);
        
        $prom = $driver->query($client, 'SHOW SESSION VARIABLES LIKE "collation\_connection"');
        self::assertInstanceOf(PromiseInterface::class, $prom);
        
        $res = $this->await($prom);
        self::assertInstanceOf(StreamQueryResultInterface::class, $res);
        
        $data = null;
        $deferred = new Deferred();
        
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
        self::assertSame(array('Variable_name' => 'collation_connection', 'Value' => 'utf8mb4_bin'), $data);
    }
    
    function testQueryCompressionEnabledPassthrough() {
        $factory = new DriverFactory($this->loop, array('compression.enable' => true));
        
        $driver = $factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306).'/information_schema');
        self::assertSame(DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->await($prom);
        self::assertSame(DriverInterface::CONNECTION_OK, $driver->getConnectionState());
        
        $client = $this->createClientMock();
        
        /** @noinspection PhpUndefinedMethodInspection */
        $client
            ->expects(self::once())
            ->method('checkinConnection')
            ->with($driver);
        
        $prom = $driver->query($client, 'SELECT 1');
        self::assertInstanceOf(PromiseInterface::class, $prom);
        
        $res = $this->await($prom);
        self::assertInstanceOf(StreamQueryResultInterface::class, $res);
        
        $data = array();
        $deferred = new Deferred();
        
        $res->once('close', function () use (&$deferred) {
            $deferred->resolve();
        });
        
        $res->on('error', function (\Throwable $e) use (&$deferred) {
            $deferred->reject($e);
        });
        
        $res->on('data', function ($row) use (&$data) {
            $data[] = $row;
        });
        
        $this->await($deferred->promise());
        self::assertCount(1, $data);
    }
    
    function testQueryCompressionEnabled() {
        $factory = new DriverFactory($this->loop, array('compression.enable' => true));
        
        $driver = $factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306).'/information_schema');
        self::assertSame(DriverInterface::CONNECTION_STARTED, $driver->getConnectionState());
        
        $this->await($prom);
        self::assertSame(DriverInterface::CONNECTION_OK, $driver->getConnectionState());
        
        $client = $this->createClientMock();
        
        /** @noinspection PhpUndefinedMethodInspection */
        $client
            ->expects(self::once())
            ->method('checkinConnection')
            ->with($driver);
        
        $prom = $driver->query($client, 'SHOW SESSION VARIABLES LIKE "%\_connection%"  -- WE NEED MORE BYTES');
        self::assertInstanceOf(PromiseInterface::class, $prom);
        
        $res = $this->await($prom);
        self::assertInstanceOf(StreamQueryResultInterface::class, $res);
        
        $data = array();
        $deferred = new Deferred();
        
        $res->once('close', function () use (&$deferred) {
            $deferred->resolve();
        });
        
        $res->on('error', function (\Throwable $e) use (&$deferred) {
            $deferred->reject($e);
        });
        
        $res->on('data', function ($row) use (&$data) {
            $data[] = $row;
        });
        
        $this->await($deferred->promise());
        self::assertGreaterThanOrEqual(1, \count($data));
    }
    
    function testPrepare() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306).'/information_schema');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        /** @noinspection PhpUndefinedMethodInspection */
        $client
            ->expects(self::once())
            ->method('checkinConnection')
            ->with($driver);
        
        $prom = $driver->prepare($client, 'SELECT * FROM `SCHEMATA` WHERE `SCHEMA_NAME` = ?');
        self::assertInstanceOf(PromiseInterface::class, $prom);
        
        $statement = $this->await($prom);
        self::assertInstanceOf(StatementInterface::class, $statement);
        
        $prom2 = $statement->execute(array('plasma_tmp'));
        self::assertInstanceOf(PromiseInterface::class, $prom2);
        
        $res = $this->await($prom2);
        self::assertInstanceOf(StreamQueryResultInterface::class, $res);
        
        $data = null;
        $deferred = new Deferred();
        
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
        self::assertNotNull($data);
        
        $this->await($statement->close());
        
        // TODO: maybe remove if statement test succeeds?
        // Unfortunately the destructor mechanism CAN NOT be tested,
        // as the destructor runs AFTER the test ends
    }
    
    function testExecute() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost:'.(\getenv('MDB_PORT') ?: 3306).'/information_schema');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        /** @noinspection PhpUndefinedMethodInspection */
        $client
            ->expects(self::once())
            ->method('checkinConnection')
            ->with($driver);
        
        $prom = $driver->execute($client, 'SELECT * FROM `SCHEMATA` WHERE `SCHEMA_NAME` = ?', array('plasma_tmp'));
        self::assertInstanceOf(PromiseInterface::class, $prom);
        
        $res = $this->await($prom);
        self::assertInstanceOf(StreamQueryResultInterface::class, $res);
        
        $data = null;
        $deferred = new Deferred();
        
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
        self::assertNotNull($data);
        
        // Waiting 2 seconds for the automatic close to occurr
        $deferredT = new Deferred();
        $this->loop->addTimer(2, array($deferredT, 'resolve'));
        
        $this->await($deferredT->promise());
    }
    
    function testGoingAwayConnect() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        self::assertNull($driver->quit());
        
        $connect = $driver->connect('whatever');
        self::assertInstanceOf(PromiseInterface::class, $connect);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Connection is going away');
        
        $this->await($connect, 0.1);
    }
    
    function testGoingAwayClose() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        self::assertNull($driver->quit());
        
        $close = $driver->close();
        self::assertInstanceOf(PromiseInterface::class, $close);
        
        $this->await($close, 0.1);
    }
    
    function testGoingAwayQuit() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
    
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        self::assertNull($driver->quit());
        
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        self::assertNull($driver->quit());
    }
    
    function testGoingAwayQuery() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        self::assertNull($driver->quit());
        
        $client = $this->createClientMock();
        
        $query = $driver->query($client, 'whatever');
        self::assertInstanceOf(PromiseInterface::class, $query);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Connection is going away');
        
        $this->await($query, 0.1);
    }
    
    function testGoingAwayPrepare() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        self::assertNull($driver->quit());
        
        $client = $this->createClientMock();
        
        $query = $driver->prepare($client, 'whatever');
        self::assertInstanceOf(PromiseInterface::class, $query);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Connection is going away');
        
        $this->await($query, 0.1);
    }
    
    function testGoingAwayExecute() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        self::assertNull($driver->quit());
        $client = $this->createClientMock();
        
        $query = $driver->execute($client, 'whatever');
        self::assertInstanceOf(PromiseInterface::class, $query);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Connection is going away');
        
        $this->await($query, 0.1);
    }
    
    function testGoingAwayBeginTransaction() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        self::assertNull($driver->quit());
        
        $client = $this->createClientMock();
        
        $query = $driver->beginTransaction($client, TransactionInterface::ISOLATION_COMMITTED);
        self::assertInstanceOf(PromiseInterface::class, $query);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Connection is going away');
        
        $this->await($query, 0.1);
    }
    
    function testGoingAwayRunCommand() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        self::assertNull($driver->quit());
        
        $cmd = new QuitCommand();
        $client = $this->createClientMock();
        
        $command = $driver->runCommand($client, $cmd);
        self::assertInstanceOf(PromiseInterface::class, $command);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Connection is going away');
        
        $this->await($command, 0.1);
    }
    
    function testGoingAwayRunQuery() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        self::assertNull($driver->quit());
        
        $client = $this->createClientMock();
        
        $query = $this->getMockBuilder(QueryBuilderInterface::class)
            ->setMethods(array(
                'create',
                'getQuery',
                'getParameters'
            ))
            ->getMock();
        
        $prom = $driver->runQuery($client, $query);
        self::assertInstanceOf(PromiseInterface::class, $prom);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Connection is going away');
        
        $this->await($prom, 0.1);
    }
    
    function testUnconnectedQuery() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to continue without connection');
        
        $client = $this->createClientMock();
        $driver->query($client, 'whatever');
    }
    
    function testUnconnectedPrepare() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to continue without connection');
        
        $client = $this->createClientMock();
        $driver->prepare($client, 'whatever');
    }
    
    function testUnconnectedExecute() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to continue without connection');
        
        $client = $this->createClientMock();
        $driver->execute($client, 'whatever');
    }
    
    function testQuote() {
        $driver = new Driver($this->loop, array('characters.set' => ''));
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost');
        $this->await($prom);
        
        $str = $driver->quote('hello "world"');
        self::assertContains($str, array(
            '"hello \"world\""',
            '"hello ""world"""'
        ));
    }
    
    function testQuoteIdentifier() {
        $driver = new Driver($this->loop, array('characters.set' => ''));
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost');
        $this->await($prom);
        
        $str = $driver->quote('hello `world`', DriverInterface::QUOTE_TYPE_IDENTIFIER);
        self::assertContains($str, array(
            '`hello \`world\``',
            '`hello ``world```'
        ));
    }
    
    function testQuoteWithOkResponse() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost');
        $this->await($prom);
        
        $str = $driver->quote('hello "world"');
        self::assertContains($str, array(
            '"hello \"world\""',
            '"hello ""world"""'
        ));
    }
    
    function testQuoteWithOkResponseIdentifier() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost');
        $this->await($prom);
        
        $str = $driver->quote('hello `world`', DriverInterface::QUOTE_TYPE_IDENTIFIER);
        self::assertContains($str, array(
            '`hello \`world\``',
            '`hello ``world```'
        ));
    }
    
    function testQuoteWithoutConnection() {
        $driver = new Driver($this->loop, array('characters.set' => ''));
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unable to continue without connection');
        
        $driver->quote('hello "world"');
    }
    
    function testQuoteQuotes() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $str = $driver->escapeUsingQuotes('UTF-8', 'hello "world"', DriverInterface::QUOTE_TYPE_VALUE);
        self::assertSame('"hello ""world"""', $str);
    }
    
    function testQuoteBackslashes() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $str = $driver->escapeUsingBackslashes('UTF-8', 'hello "world"', DriverInterface::QUOTE_TYPE_VALUE);
        self::assertSame('"hello \"world\""', $str);
    }
    
    function testQuoteIdentifierQuotes() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $str = $driver->escapeUsingQuotes('UTF-8', 'hello`_world', DriverInterface::QUOTE_TYPE_IDENTIFIER);
        self::assertSame('`hello``_world`', $str);
    }
    
    function testQuoteIdentifierBackslashes() {
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $str = $driver->escapeUsingBackslashes('UTF-8', 'hello`_world', DriverInterface::QUOTE_TYPE_IDENTIFIER);
        self::assertSame('`hello\`_world`', $str);
    }
    
    function testTextTypeString() {
        $values = array();
        
        for($i = 0; $i < 18; $i++) {
            $values[] = '""';
        }
        
        $values[1] = '"hello_world"';
        
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost/plasma_tmp');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $this->await($driver->query($client, "SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'"));
        
        $prep = $driver->query($client, 'INSERT INTO `test_strings` VALUES ('.\implode(', ', $values).')');
        $result = $this->await($prep);
        
        self::assertSame(1, $result->getAffectedRows());
        
        $selprep = $driver->query($client, 'SELECT * FROM `test_strings`');
        $select = $this->await($selprep);
        
        $dataProm = first($select);
        $data = $this->await($dataProm);
        
        $this->await($driver->query($client, 'TRUNCATE TABLE `test_strings`'));
        
        self::assertSame(array(
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
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost/plasma_tmp');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $this->await($driver->query($client, "SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'"));
        
        $prep = $driver->query($client, 'INSERT INTO `test_ints` VALUES ('.\implode(', ', $values).')');
        $result = $this->await($prep);
        
        self::assertSame(1, $result->getAffectedRows());
        
        $selprep = $driver->query($client, 'SELECT * FROM `test_ints`');
        $select = $this->await($selprep);
        
        $dataProm = first($select);
        $data = $this->await($dataProm);
        
        $this->await($driver->query($client, 'TRUNCATE TABLE `test_ints`'));
        
        self::assertSame(array(
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
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost/plasma_tmp');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $this->await($driver->query($client, "SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'"));
        
        $prep = $driver->query($client, 'INSERT INTO `test_floats` VALUES ('.\implode(', ', $values).')');
        $result = $this->await($prep);
        
        self::assertSame(1, $result->getAffectedRows());
        
        $selprep = $driver->query($client, 'SELECT * FROM `test_floats`');
        $select = $this->await($selprep);
        
        $dataProm = first($select);
        $data = $this->await($dataProm);
        
        $this->await($driver->query($client, 'TRUNCATE TABLE `test_floats`'));
        
        // Round single precision float to 1 decimal
        $data['testcol1'] = \round($data['testcol1'], 1);
        
        self::assertSame(array(
            'testcol1' => 0.9,
            'testcol2' => 4.3
        ), $data);
    }
    
    function testTextTypeDate() {
        $values = array('"2011-03-05"', '"2011-03-05 00:00:00"', '"23:41:03"', 'null');
        
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost/plasma_tmp');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $this->await($driver->query($client, "SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'"));
        
        $prep = $driver->query($client, 'INSERT INTO `test_dates` VALUES ('.\implode(', ', $values).')');
        $result = $this->await($prep);
        
        self::assertSame(1, $result->getAffectedRows());
        
        $selprep = $driver->query($client, 'SELECT * FROM `test_dates`');
        $select = $this->await($selprep);
        
        $dataProm = first($select);
        $data = $this->await($dataProm);
        
        $this->await($driver->query($client, 'TRUNCATE TABLE `test_dates`'));
        
        $timestamp = \time();
        $ts = \DateTime::createFromFormat('Y-m-d H:i:s', $data['testcol4'])->getTimestamp();
        unset($data['testcol4']);
        
        self::assertSame(array(
            'testcol1' => '2011-03-05',
            'testcol2' => '2011-03-05 00:00:00',
            'testcol3' => '23:41:03'
        ), $data);
        
        // We're happy if we're +/- 1 minute correct
        self::assertLessThanOrEqual(($timestamp + 60), $ts);
        self::assertGreaterThanOrEqual(($timestamp - 60), $ts);
    }
    
    function testUnsupportedTypeForBindingParameters() {
        $values = array();
        
        for($i = 0; $i < 18; $i++) {
            $values[] = '';
        }
        
        $values[0] = array('hello', 'world');
        
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost/plasma_tmp');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        $this->await($driver->query($client, "SET SESSION sql_mode = 'NO_ENGINE_SUBSTITUTION'"));
        
        $prep = $driver->execute(
            $client,
            'INSERT INTO `test_strings` VALUES ('.\implode(', ', \array_fill(0, 18, '?')).')',
            $values
        );
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unexpected type for binding parameter: array');
        
        $this->await($prep);
    }
    
    function testReadCursor() {
        /** @var Driver  $driver */
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
        $prom = $this->connect($driver, 'localhost/plasma_tmp');
        $this->await($prom);
        
        $client = $this->createClientMock();
        
        if(\getenv('SCRUTINIZER') || \getenv('TRAVIS')) {
            self::assertTrue($driver->supportsCursors());
        }
        
        if(!$driver->supportsCursors()) {
            $this->expectException(\LogicException::class);
        }
        
        $cursor = $this->await($driver->createReadCursor($client, 'SELECT * FROM test_cursors'));
        self::assertInstanceOf(StatementCursor::class, $cursor);
        
        /** @noinspection PhpUndefinedMethodInspection */
        $client
            ->expects(self::once())
            ->method('checkinConnection')
            ->with($driver);
        
        $row = $this->await($cursor->fetch());
        self::assertSame(array('testcol' => 'HELLO'), $row);
        
        $row2 = $this->await($cursor->fetch());
        self::assertSame(array('testcol' => 'WORLD'), $row2);
        
        $row3_4 = $this->await($cursor->fetch(2));
        self::assertSame(array(
            array('testcol' => 'PLASMA'),
            array('testcol' => 'IN')
        ), $row3_4);
        
        $row5 = $this->await($cursor->fetch());
        self::assertSame(array('testcol' => 'ACTION'), $row5);
        
        $falsy = $this->await($cursor->fetch());
        self::assertFalse($falsy);
        
        sleep(0.1, $this->loop);
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
        self::assertInstanceOf(DriverInterface::class, $driver);
        
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
        
        self::assertSame(1, $result->getAffectedRows());
        
        $selprep = $driver->execute($client, 'SELECT * FROM `test_strings`');
        $select = $this->await($selprep);
        
        $dataProm = first($select);
        $data = $this->await($dataProm);
        
        $this->await($driver->query($client, 'TRUNCATE TABLE `test_strings`'));
        return $data;
    }
    
    function testBinaryTypeChar() {
        $data = $this->insertIntoTestString(0, 'hell');
        
        self::assertSame(array(
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
        
        self::assertSame(array(
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
        
        self::assertSame(array(
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
        
        self::assertSame(array(
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
        
        self::assertSame(array(
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
        
        self::assertSame(array(
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
        
        self::assertSame(array(
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
        
        self::assertSame(array(
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
        
        self::assertSame(array(
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
        
        self::assertSame(array(
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
        
        self::assertSame(array(
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
        
        self::assertSame(array(
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
        
        self::assertSame(array(
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
        
        self::assertSame(array(
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
        
        self::assertSame(array(
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
        
        self::assertSame(array(
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
    
    /* JSON is supported since MySQL 5.7.8
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
        self::assertInstanceOf(DriverInterface::class, $driver);
        
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
        
        self::assertSame(1, $result->getAffectedRows());
        
        $selprep = $driver->execute($client, 'SELECT * FROM `test_ints`');
        $select = $this->await($selprep);
        
        $dataProm = first($select);
        $data = $this->await($dataProm);
        
        $this->await($driver->query($client, 'TRUNCATE TABLE `test_ints`'));
        return $data;
    }
    
    function testBinaryTypeTinyBoolean() {
        $values = array(true, 0, 0, 0, 0, 0);
        
        $driver = $this->factory->createDriver();
        self::assertInstanceOf(DriverInterface::class, $driver);
        
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
        
        self::assertSame(1, $result->getAffectedRows());
        
        $selprep = $driver->execute($client, 'SELECT * FROM `test_ints`');
        $select = $this->await($selprep);
        
        $dataProm = first($select);
        $data = $this->await($dataProm);
        
        $this->await($driver->query($client, 'TRUNCATE TABLE `test_ints`'));
        
        self::assertSame(array(
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
        
        self::assertSame(array(
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
        
        self::assertSame(array(
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
        
        self::assertSame(array(
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
        
        self::assertSame(array(
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
        
        self::assertSame(array(
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
        
        self::assertSame(array(
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
        self::assertInstanceOf(DriverInterface::class, $driver);
        
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
        
        self::assertSame(1, $result->getAffectedRows());
        
        $selprep = $driver->execute($client, 'SELECT * FROM `test_floats`');
        $select = $this->await($selprep);
        
        $dataProm = first($select);
        $data = $this->await($dataProm);
        
        $this->await($driver->query($client, 'TRUNCATE TABLE `test_floats`'));
        return $data;
    }
    
    function testBinaryTypeFloat() {
        $data = $this->insertIntoTestFloat(0, 5.2);
        self::assertSame(5.2, \round($data['testcol1'], 1));
    }
    
    function testBinaryTypeDouble() {
        $data = $this->insertIntoTestFloat(1, 25.2543543143);
        self::assertSame(25.25, \round($data['testcol2'], 2));
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
        self::assertInstanceOf(DriverInterface::class, $driver);
        
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
        
        self::assertSame(1, $result->getAffectedRows());
        
        $selprep = $driver->execute($client, 'SELECT * FROM `test_dates`');
        $select = $this->await($selprep);
        
        $dataProm = first($select);
        $data = $this->await($dataProm);
        
        $this->await($driver->query($client, 'TRUNCATE TABLE `test_dates`'));
        return $data;
    }
    
    function testBinaryTypeDate() {
        $data = $this->insertIntoTestDate(0, '2011-03-05');
        
        self::assertSame(array(
            'testcol1' => '2011-03-05',
            'testcol2' => '0000-00-00 00:00:00.000 000',
            'testcol3' => '0d 00:00:00',
            'testcol4' => '0000-00-00 00:00:00.000 000'
        ), $data);
    }
    
    function testBinaryTypeDateZero() {
        $data = $this->insertIntoTestDate(0, '0000-00-00');
        
        self::assertSame(array(
            'testcol1' => '0000-00-00',
            'testcol2' => '0000-00-00 00:00:00.000 000',
            'testcol3' => '0d 00:00:00',
            'testcol4' => '0000-00-00 00:00:00.000 000'
        ), $data);
    }
    
    function testBinaryTypeDateTime() {
        $data = $this->insertIntoTestDate(1, '2011-03-05 00:00:00.000 000');
        
        self::assertSame(array(
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
        
        self::assertSame(array(
            'testcol1' => '0000-00-00',
            'testcol2' => '0000-00-00 00:00:00.000 000',
            'testcol3' => '0d 00:00:00',
            'testcol4' => '0000-00-00 00:00:00.000 000'
        ), $data);
    }
    
    function testBinaryTypeTime() {
        $data = $this->insertIntoTestDate(2, '23:41:03.000 000');
        
        self::assertSame(array(
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
        
        self::assertSame(array(
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
        
        self::assertSame(array(
            'testcol1' => '0000-00-00',
            'testcol2' => '0000-00-00 00:00:00.000 000',
            'testcol3' => '0d 00:00:00'
        ), $data);
        
        // We're happy if we're +/- 1 minute correct
        self::assertLessThanOrEqual(($timestamp + 60), $ts);
        self::assertGreaterThanOrEqual(($timestamp - 60), $ts);
    }
    
    function testBinaryTypeTimestampZeroed() {
        $data = $this->insertIntoTestDate(3, '0000-00-00 00:00:00');
        
        self::assertSame(array(
            'testcol1' => '0000-00-00',
            'testcol2' => '0000-00-00 00:00:00.000 000',
            'testcol3' => '0d 00:00:00',
            'testcol4' => '0000-00-00 00:00:00.000 000'
        ), $data);
    }
    
    function createClientMock(): ClientInterface {
        return $this->getMockBuilder(ClientInterface::class)
            ->setMethods(array(
                'create',
                'getConnectionCount',
                'checkinConnection',
                'beginTransaction',
                'close',
                'quit',
                'runCommand',
                'runQuery',
                'createReadCursor',
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
