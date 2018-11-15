<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018 PlasmaPHP, All Rights Reserved
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
     * @var \Plasma\DriverInterface
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
     * @param \Plasma\DriverInterface              $driver
     * @param mixed                                $id
     * @param string                               $query
     * @param string                               $rQuery
     * @param array                                $rParams
     * @param \Plasma\ColumnDefinitionInterface[]  $params
     * @param \Plasma\ColumnDefinitionInterface[]  $columns
     */
    function __construct(\Plasma\ClientInterface $client, \Plasma\DriverInterface $driver, $id, string $query, string $rQuery, array $rParams, array $params, array $columns) {
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
     * @throws \Plasma\Exception
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
        
        $close->once('end', function () {
            $this->client->checkinConnection($this->driver);
        });
        
        return $close->getPromise();
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
        
        if(\count($params) < \count($this->params)) {
            throw new \Plasma\Exception('Not enough parameters for this statement, expected '.\count($this->params).', got '.\count($params));
        }
        
        $realParams = array();
        $pos = (\array_key_exists(0, $params) ? 0 : 1);
        
        foreach($this->rewrittenParams as $param) {
            $key = ($param[0] === ':' ? $param : ($pos++));
            
            if(!\array_key_exists($key, $params)) {
                throw new \Plasma\Exception('Missing parameter with key "'.$key.'"');
            }
            
            $realParams[] = $params[$key];
        }
        
        $execute = new \Plasma\Drivers\MySQL\Commands\StatementExecuteCommand($this->driver, $this->id, $this->query, $realParams);
        $this->driver->executeCommand($execute);
        
        return $execute->getPromise();
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
