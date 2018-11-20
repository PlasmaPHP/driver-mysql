<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL\Tests;

class StatementTest extends TestCase {
    /**
     * @var \Plasma\Drivers\MySQL\Driver
     */
    public $driver;
    
    function testGetID() {
        $statement = $this->getStatement();
        $this->assertSame(42, $statement->getID());
    }
    
    function testGetQuery() {
        $statement = $this->getStatement();
        $this->assertSame('SELECT * FROM `users` WHERE `id` = :id', $statement->getQuery());
    }
    
    function testCloseDouble() {
        $statement = $this->getStatement();
        $this->assertFalse($statement->isClosed());
        
        $this->driver
            ->expects($this->once())
            ->method('executeCommand')
            ->will($this->returnCallback(function (\Plasma\CommandInterface $command) {
                $command->onComplete();
            }));
        
        $close = $statement->close();
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $close);
        
        $this->assertTrue($statement->isClosed());
        
        $close = $statement->close();
        $this->assertInstanceOf(\React\Promise\PromiseInterface::class, $close);
    }
    
    function testGetParams() {
        $statement = $this->getStatement();
        $this->assertEquals(array(
            (new \Plasma\ColumnDefinition('plasma_tmp', 'test', 'test_field', 'BIGINT', 'utf8mb4', null, false, 0, null))
        ), $statement->getParams());
    }
    
    function testGetColumns() {
        $statement = $this->getStatement();
        $this->assertEquals(array(
            (new \Plasma\ColumnDefinition('plasma_tmp', 'test5', 'test_field', 'BIGINT', 'utf8mb4', null, false, 0, null))
        ), $statement->getColumns());
    }
    
    function getStatement(): \Plasma\Drivers\MySQL\Statement {
        $client = $this->createClientMock();
        $this->driver = $this->getMockBuilder(\Plasma\Drivers\MySQL\Driver::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $query = 'SELECT * FROM `users` WHERE `id` = :id';
        $queryR = 'SELECT * FROM `users` WHERE `id` = ?';
        $paramsR = array(0 => ':id');
        
        $params = array(
            (new \Plasma\ColumnDefinition('plasma_tmp', 'test', 'test_field', 'BIGINT', 'utf8mb4', null, false, 0, null))
        );
        
        $columns = array(
            (new \Plasma\ColumnDefinition('plasma_tmp', 'test5', 'test_field', 'BIGINT', 'utf8mb4', null, false, 0, null))
        );
        
        $statement = new \Plasma\Drivers\MySQL\Statement($client, $this->driver, 42, $query, $queryR, $paramsR, $params, $columns);
        return $statement;
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
