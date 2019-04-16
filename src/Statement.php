<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL;

/**
 * Represents a Prepared Statement.
 */
class Statement implements \Plasma\StatementInterface {
    /**
     * @var \Plasma\ClientInterface
     */
    protected $client;
    
    /**
     * @var \Plasma\Drivers\MySQL\Driver
     */
    protected $driver;
    
    /**
     * @var mixed
     */
    protected $id;
    
    /**
     * @var string
     */
    protected $query;
    
    /**
     * @var string
     */
    protected $rewrittenQuery;
    
    /**
     * @var array
     */
    protected $rewrittenParams;
    
    /**
     * @var \Plasma\ColumnDefinitionInterface[]
     */
    protected $params;
    
    /**
     * @var \Plasma\ColumnDefinitionInterface[]
     */
    protected $columns;
    
    /**
     * @var bool
     */
    protected $closed = false;
    
    /**
     * Constructor.
     * @param \Plasma\ClientInterface              $client
     * @param \Plasma\Drivers\MySQL\Driver         $driver
     * @param mixed                                $id
     * @param string                               $query
     * @param string                               $rQuery
     * @param array                                $rParams
     * @param \Plasma\ColumnDefinitionInterface[]  $params
     * @param \Plasma\ColumnDefinitionInterface[]  $columns
     */
    function __construct(
        \Plasma\ClientInterface $client, \Plasma\Drivers\MySQL\Driver $driver, $id, string $query, string $rQuery, array $rParams, array $params, array $columns
    ) {
        $this->client = $client;
        $this->driver = $driver;
        
        $this->id = $id;
        $this->query = $query;
        $this->rewrittenQuery = $rQuery;
        $this->rewrittenParams = $rParams;
        $this->params = $params;
        $this->columns = $columns;
    }
    
    /**
     * Destructor. Runs once the instance goes out of scope.
     * Please do not rely on the destructor to properly close your statement.
     * ALWAYS explicitely close the statement once you're done.
     * @codeCoverageIgnore
     */
    function __destruct() {
        if(!$this->closed) {
            $this->close()->then(null, function () {
                // Error during implicit close, close the session
                $this->driver->close();
            });
        }
    }
    
    /**
     * Get the driver-dependent ID of this statement.
     * The return type can be of ANY type, as the ID depends on the driver and DBMS.
     * @return mixed
     */
    function getID() {
        return $this->id;
    }
    
    /**
     * Get the prepared query.
     * @return string
     */
    function getQuery(): string {
        return $this->query;
    }
    
    /**
     * Whether the statement has been closed.
     * @return bool
     */
    function isClosed(): bool {
        return $this->closed;
    }
    
    /**
     * Closes the prepared statement and frees the associated resources on the server.
     * Closing a statement more than once has no effect.
     * @return \React\Promise\PromiseInterface
     */
    function close(): \React\Promise\PromiseInterface {
        if($this->closed) {
            return \React\Promise\resolve();
        }
        
        $this->closed = true;
        
        $close = new \Plasma\Drivers\MySQL\Commands\StatementCloseCommand($this->driver, $this->id);
        $this->driver->executeCommand($close);
        
        return $close->getPromise()->then(function () {
            $this->client->checkinConnection($this->driver);
        });
    }
    
    /**
     * Executes the prepared statement. Resolves with a `QueryResult` instance.
     * @param array  $params
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\Exception  Thrown if the statement is executed after it has been closed, or thrown for insufficent or missing parameters.
     * @see \Plasma\QueryResultInterface
     */
    function execute(array $params = array()): \React\Promise\PromiseInterface {
        if($this->closed) {
            throw new \Plasma\Exception('Statement has been closed');
        }
        
        $params = \Plasma\Utility::replaceParameters($this->rewrittenParams, $params);
        
        $execute = new \Plasma\Drivers\MySQL\Commands\StatementExecuteCommand($this->driver, $this->id, $this->query, $params, $this->params);
        $this->driver->executeCommand($execute);
        
        return $execute->getPromise();
    }
    
    /**
     * Runs the given querybuilder on the underlying driver instance. However the query will be ignored, only the parameters are used.
     * The driver CAN throw an exception if the given querybuilder is not supported.
     * An example would be a SQL querybuilder and a Cassandra driver.
     * @param \Plasma\QueryBuilderInterface  $query
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\Exception
     */
    function runQuery(\Plasma\QueryBuilderInterface $query): \React\Promise\PromiseInterface {
        return $this->execute($query->getParameters());
    }
    
    /**
     * Creates a cursor.
     * @param array  $params
     * @return \React\Promise\PromiseInterface
     * @throws \LogicException    Thrown if the driver or DBMS does not support cursors.
     * @throws \Plasma\Exception  Thrown if the statement is executed after it has been closed, or if it's not a SELECT query, or for insufficent or missing parameters.
     * @internal
     */
    function createCursor(array $params = array()): \React\Promise\PromiseInterface {
        if($this->closed) {
            throw new \Plasma\Exception('Statement has been closed');
        } elseif(empty($this->columns)) {
            throw new \Plasma\Exception('Query is not a SELECT query');
        } elseif(!$this->driver->supportsCursors()) {
            throw new \LogicException('Used DBMS version does not support cursors');
        }
        
        $params = \Plasma\Utility::replaceParameters($this->rewrittenParams, $params);
        
        $execute = new \Plasma\Drivers\MySQL\Commands\StatementExecuteCommand($this->driver, $this->id, $this->query, $params, $this->params, 0x05);
        $this->driver->executeCommand($execute);
        
        return $execute->getPromise()->then(function () {
            return (new \Plasma\Drivers\MySQL\StatementCursor($this->driver, $this));
        });
    }
    
    /**
     * Get the parsed parameters.
     * @return \Plasma\ColumnDefinitionInterface[]
     */
    function getParams(): array {
        return $this->params;
    }
    
    /**
     * Get the columns.
     * @return \Plasma\ColumnDefinitionInterface[]
     */
    function getColumns(): array {
        return $this->columns;
    }
}
