<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
 */

namespace Plasma\Drivers\MySQL;

use Plasma\ClientInterface;
use Plasma\ColumnDefinitionInterface;
use Plasma\Drivers\MySQL\Commands\StatementCloseCommand;
use Plasma\Drivers\MySQL\Commands\StatementExecuteCommand;
use Plasma\Exception;
use Plasma\QueryBuilderInterface;
use Plasma\StatementInterface;
use Plasma\Utility;
use React\Promise;
use React\Promise\PromiseInterface;

/**
 * Represents a Prepared Statement.
 */
class Statement implements StatementInterface {
    /**
     * @var ClientInterface
     */
    protected $client;
    
    /**
     * @var Driver
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
     * @var ColumnDefinitionInterface[]
     */
    protected $params;
    
    /**
     * @var ColumnDefinitionInterface[]
     */
    protected $columns;
    
    /**
     * @var bool
     */
    protected $closed = false;
    
    /**
     * Constructor.
     * @param ClientInterface              $client
     * @param Driver                       $driver
     * @param mixed                        $id
     * @param string                       $query
     * @param string                       $rQuery
     * @param array                        $rParams
     * @param ColumnDefinitionInterface[]  $params
     * @param ColumnDefinitionInterface[]  $columns
     */
    function __construct(
        ClientInterface $client,
        Driver $driver,
        $id,
        string $query,
        string $rQuery,
        array $rParams,
        array $params,
        array $columns
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
            $this->close()->then(null,function () {
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
     * @return PromiseInterface
     */
    function close(): PromiseInterface {
        if($this->closed) {
            return Promise\resolve();
        }
        
        $this->closed = true;
        
        $close = new StatementCloseCommand($this->driver, $this->id);
        $this->driver->executeCommand($close);
        
        return $close->getPromise()->then(function () {
            $this->client->checkinConnection($this->driver);
        });
    }
    
    /**
     * Executes the prepared statement. Resolves with a `QueryResult` instance.
     * @param array  $params
     * @return PromiseInterface
     * @throws Exception  Thrown if the statement is executed after it has been closed, or thrown for insufficent or missing parameters.
     * @see \Plasma\QueryResultInterface
     */
    function execute(array $params = array()): PromiseInterface {
        if($this->closed) {
            throw new Exception('Statement has been closed');
        }
        
        $params = Utility::replaceParameters($this->rewrittenParams, $params);
        
        $execute = new StatementExecuteCommand($this->driver, $this->id, $this->query, $params, $this->params);
        $this->driver->executeCommand($execute);
        
        return $execute->getPromise();
    }
    
    /**
     * Runs the given querybuilder on the underlying driver instance. However the query will be ignored, only the parameters are used.
     * The driver CAN throw an exception if the given querybuilder is not supported.
     * An example would be a SQL querybuilder and a Cassandra driver.
     * @param QueryBuilderInterface  $query
     * @return PromiseInterface
     * @throws Exception
     */
    function runQuery(QueryBuilderInterface $query): PromiseInterface {
        return $this->execute($query->getParameters());
    }
    
    /**
     * Creates a cursor.
     * @param array  $params
     * @return PromiseInterface
     * @throws \LogicException    Thrown if the driver or DBMS does not support cursors.
     * @throws Exception  Thrown if the statement is executed after it has been closed, or if it's not a SELECT query, or for insufficent or missing parameters.
     * @internal
     */
    function createReadCursor(array $params = array()): PromiseInterface {
        if($this->closed) {
            throw new Exception('Statement has been closed');
        } elseif(empty($this->columns)) {
            throw new Exception('Query is not a SELECT query');
        } elseif(!$this->driver->supportsCursors()) {
            throw new \LogicException('Used DBMS version does not support cursors');
        }
        
        $params = Utility::replaceParameters($this->rewrittenParams, $params);
        
        $execute = new StatementExecuteCommand($this->driver, $this->id, $this->query, $params, $this->params, 0x05);
        $this->driver->executeCommand($execute);
        
        return $execute->getPromise()->then(function () {
            return (new StatementCursor($this->driver, $this));
        });
    }
    
    /**
     * Get the parsed parameters.
     * @return ColumnDefinitionInterface[]
     */
    function getParams(): array {
        return $this->params;
    }
    
    /**
     * Get the columns.
     * @return ColumnDefinitionInterface[]
     */
    function getColumns(): array {
        return $this->columns;
    }
}
