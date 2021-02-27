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
use Plasma\Drivers\MySQL\ColumnDefinition;
use Plasma\Drivers\MySQL\Driver;
use Plasma\Drivers\MySQL\Messages\HandshakeMessage;
use Plasma\Drivers\MySQL\ProtocolParser;
use Plasma\Drivers\MySQL\Statement;
use Plasma\Exception;
use React\Promise\PromiseInterface;

class StatementTest extends TestCase {
    /**
     * @var Driver
     */
    public $driver;
    
    function testGetID() {
        $statement = $this->getStatement();
        self::assertSame(42, $statement->getID());
    }
    
    function testGetQuery() {
        $statement = $this->getStatement();
        self::assertSame('SELECT * FROM `users` WHERE `id` = :id', $statement->getQuery());
    }
    
    function testCloseDouble() {
        $statement = $this->getStatement();
        self::assertFalse($statement->isClosed());
        
        /** @noinspection PhpUndefinedMethodInspection */
        $this->driver
            ->expects(self::once())
            ->method('executeCommand');
        
        $close = $statement->close();
        self::assertInstanceOf(PromiseInterface::class, $close);
        
        self::assertTrue($statement->isClosed());
        
        $close = $statement->close();
        self::assertInstanceOf(PromiseInterface::class, $close);
    }
    
    function testGetParams() {
        $statement = $this->getStatement();
        
        self::assertEquals(array(
            (new ColumnDefinition('test', 'test_field', 'BIGINT', 'utf8mb4', null, 0, null))
        ), $statement->getParams());
    }
    
    function testGetColumns() {
        $statement = $this->getStatement();
        
        self::assertEquals(array(
            (new ColumnDefinition('test5', 'test_field', 'BIGINT', 'utf8mb4', null, 0, null))
        ), $statement->getColumns());
    }
    
    function testExecute() {
        $statement = $this->getStatement();
        
        $parser = $this->getMockBuilder(ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        /** @noinspection PhpUndefinedMethodInspection */
        $this->driver
            ->expects(self::exactly(2)) // +1 close
            ->method('executeCommand');
        
        /** @noinspection PhpUndefinedMethodInspection */
        $this->driver
            ->expects(self::once())
            ->method('getHandshake')
            ->willReturn((new HandshakeMessage($parser)));
        
        $exec = $statement->execute(array(':id' => 5));
        self::assertInstanceOf(PromiseInterface::class, $exec);
    }
    
    function testExecuteAlreadyClosed() {
        $statement = $this->getStatement();
        
        /** @noinspection PhpUndefinedMethodInspection */
        $this->driver
            ->expects(self::once())
            ->method('executeCommand');
        
        $close = $statement->close();
        self::assertInstanceOf(PromiseInterface::class, $close);
        
        self::assertTrue($statement->isClosed());
        
        $this->expectException(Exception::class);
        $statement->execute(array(':id' => 5));
    }
    
    function testExecuteMissingParams() {
        $statement = $this->getStatement();
        
        /** @noinspection PhpUndefinedMethodInspection */
        $this->driver
            ->expects(self::once()) // 1 close
            ->method('executeCommand');
        
        $this->expectException(Exception::class);
        $statement->execute(array());
    }
    
    function testExecuteUnknownParam() {
        $statement = $this->getStatement();
        
        /** @noinspection PhpUndefinedMethodInspection */
        $this->driver
            ->expects(self::once()) // 1 close
            ->method('executeCommand');
        
        $this->expectException(Exception::class);
        $statement->execute(array(':help' => 1252));
    }
    
    function getStatement(): Statement {
        $client = $this->createClientMock();
        $this->driver = $this->getMockBuilder(Driver::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $query = 'SELECT * FROM `users` WHERE `id` = :id';
        $queryR = 'SELECT * FROM `users` WHERE `id` = ?';
        $paramsR = array(0 => ':id');
        
        $params = array(
            (new ColumnDefinition('test', 'test_field', 'BIGINT', 'utf8mb4', null, 0, null))
        );
        
        $columns = array(
            (new ColumnDefinition('test5', 'test_field', 'BIGINT', 'utf8mb4', null, 0, null))
        );
    
        return (new Statement($client, $this->driver, 42, $query, $queryR, $paramsR, $params, $columns));
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
