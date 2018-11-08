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
 * The MySQL Driver.
 * @internal
 */
class Driver implements \Plasma\DriverInterface {
    use \Evenement\EventEmitterTrait;
    
    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;
    
    /**
     * @var array
     */
    protected $options;
    
    /**
     * @var \React\Socket\ConnectorInterface
     */
    protected $connector;
    
    /**
     * Internal class is intentional used, as there's no other way currently.
     * @see https://github.com/reactphp/socket/issues/180
     * @var \React\Socket\StreamEncryption
     */
    protected $encryption;
    
    /**
     * @var \React\Socket\Connection
     */
    protected $connection;
    
    /**
     * @var int
     */
    protected $connectionState = \Plasma\DriverInterface::CONNECTION_CLOSED;
    
    /**
     * @var \SplQueue
     */
    protected $queue;
    
    /**
     * @var int
     */
    protected $busy = \Plasma\DriverInterface::STATE_IDLE;
    
    /**
     * @var bool
     */
    protected $transaction = false;
    
    /**
     * Constructor.
     */
    function __construct(\React\EventLoop\LoopInterface $loop, array $options) {
        if(!\function_exists('stream_socket_enable_crypto')) {
            throw new \LogicException('Encryption is not supported on your platform');
        }
        
        $this->validateOptions($options);
        $this->options = $options;
        
        $this->connector = ($this->options['connector'] ?? (new \React\Socket\Connector($loop)));
        $this->encryption = new \React\Socket\StreamEncryption($this->loop, false);
        $this->queue = new \SplQueue();
    }
    
    /**
     * Retrieves the current connection state.
     * @return int
     */
    function getConnectionState(): int {
        return $this->connectionState;
    }
    
    /**
     * Retrieves the current busy state.
     * @return int
     */
    function getBusyState(): int {
        return $this->busy;
    }
    
    /**
     * Get the length of the driver backlog queue.
     * @return int
     */
    function getBacklogLength(): int {
        return $this->queue->count();
    }
    
    /**
     * Connects to the given URI.
     * @return \React\Promise\PromiseInterface
     */
    function connect(string $uri): \React\Promise\PromiseInterface {
        return $this->connector->connect($uri)->then(function (\React\Socket\ConnectionInterface $connection) {
            // See description of property encryption
            if(!($connection instanceof \React\Socket\Connection)) {
                throw new \LogicException('Custom connection class is NOT supported yet (encryption limitation)');
            }
            
            $connection->on('close', function () {
                $this->connection = null;
                $this->connectionState = \Plasma\DriverInterface::CONNECTION_UNUSABLE;
                
                $this->emit('close');
            });
            
            $this->connection = $connection;
        });
    }
    
    /**
     * Pauses the underlying stream I/O consumption.
     * If consumption is already paused, this will do nothing.
     * @return bool  Whether the operation was successful.
     */
    function pauseStreamConsumption(): bool {
        $this->connection->pause();
        return true;
    }
    
    /**
     * Resumes the underlying stream I/O consumption.
     * If consumption is not paused, this will do nothing.
     * @return bool  Whether the operation was successful.
     */
    function resumeStreamConsumption(): bool {
        $this->connection->resume();
        return true;
    }
    
    /**
     * Closes all connections gracefully after processing all outstanding requests.
     * @return \React\Promise\PromiseInterface
     */
    function close(): \React\Promise\PromiseInterface {
        $this->goingAway = new \React\Promise\Deferred();
        
        if($this->queue->count() === 0) {
            $this->goingAway->resolve();
        }
        
        return $this->goingAway->promise()->then(function () {
            $this->quit();
        });
    }
    
    /**
     * Forcefully closes the connection, without waiting for any outstanding requests. This will reject all outstanding requests.
     * @return void
     */
    function quit(): void { // TODO
        /** @var \Plasma\CommandInterface  $command */
        while($command = $this->queue->shift()) {
            $command->emit('error', array((new \Plasma\Exception('Connection is going away'))));
        }
        
        $this->connection->close();
    }
    
    /**
     * Whether this driver is currently in a transaction.
     * @return bool
     */
    function isInTransaction(): bool {
        return $this->transaction;
    }
    
    /**
     * Begins a transaction. Resolves with a `TransactionInterface` instance.
     *
     * Checks out a connection until the transaction gets committed or rolled back.
     * It must be noted that the user is responsible for finishing the transaction. The client WILL NOT automatically
     * check the connection back into the pool, as long as the transaction is not finished.
     *
     * Some databases, including MySQL, automatically issue an implicit COMMIT when a database definition language (DDL)
     * statement such as DROP TABLE or CREATE TABLE is issued within a transaction.
     * The implicit COMMIT will prevent you from rolling back any other changes within the transaction boundary.
     * @param \Plasma\ClientInterface  $client
     * @param int                      $isolation  See the `TransactionInterface` constants.
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\Exception
     * @see \Plasma\TransactionInterface
     */
    function beginTransaction(\Plasma\ClientInterface $client, int $isolation = \Plasma\TransactionInterface::ISOLATION_COMMITTED): \React\Promise\PromiseInterface {
        if($this->transaction) {
            throw new \Plasma\Exception('Driver is already in transaction');
        }
        
        switch ($isolation) {
            case \Plasma\TransactionInterface::ISOLATION_UNCOMMITTED:
                $query = 'SET SESSION TRANSACTION ISOLATION LEVEL READ UNCOMMITTED';
            break;
            case \Plasma\TransactionInterface::ISOLATION_COMMITTED:
                $query = 'SET SESSION TRANSACTION ISOLATION LEVEL READ COMMITTED';
            break;
            case \Plasma\TransactionInterface::ISOLATION_REPEATABLE:
                $query = 'SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ';
            break;
            case \Plasma\TransactionInterface::ISOLATION_SERIALIZABLE:
                $query = 'SET SESSION TRANSACTION ISOLATION LEVEL SERIALIZABLE';
            break;
            default:
                throw new \Plasma\Exception('Invalid isolation level given');
            break;
        }
        
        return $this->query($query)->then(function () {
            return $this->query('START TRANSACTION');
        })->then(function () use (&$client, $isolation) {
            $this->transaction = true;
            return (new \Plasma\Transaction($client, $this, $isolation));
        });
    }
    
    /**
     * Informationally closes a transaction. This method is used by `Transaction` to inform the driver of the end of the transaction.
     * @return void
     */
    function endTransaction(): void {
        $this->transaction = false;
    }
    
    /**
     * Executes a plain query. Resolves with a `QueryResultInterface` instance.
     * @param string  $query
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\Exception
     * @see \Plasma\QueryResultInterface
     */
    function query(string $query): \React\Promise\PromiseInterface {
        
    }
    
    /**
     * Prepares a query. Resolves with a `StatementInterface` instance.
     * @param string  $query
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\Exception
     * @see \Plasma\StatementInterface
     */
    function prepare(string $query): \React\Promise\PromiseInterface {
        
    }
    
    /**
     * Quotes the string for use in the query.
     * @param string  $str
     * @return string
     * @throws \LogicException  Thrown if the driver does not support quoting.
     * @throws \Plasma\Exception
     */
    function quote(string $str): string {
        
    }
    
    /**
     * Enables TLS on the connection.
     * @return \React\Promise\PromiseInterface
     */
    function enableTLS(): \React\Promise\PromiseInterface {
        // Set required SSL/TLS context options
        foreach(($this->options['tls.context'] ?? array()) as $name => $value) {
            \stream_context_set_option($this->connection->stream, 'ssl', $name, $value);
        }
        
        return $this->encryption->enable($this->connection)->otherwise(function (\Throwable $error) {
            $this->connection->close();
            throw new \RuntimeException('Connection failed during TLS handshake: '.$error->getMessage(), $error->getCode());
        });
    }
    
    /**
     * Validates the given options.
     * @param array  $options
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function validateOptions(array $options) {
        $validator = \CharlotteDunois\Validation\Validator::make($options, array(
            'connector' => 'class:\React\Socket\ConnectorInterface=object',
            'tls.context' => 'array'
        ));
        
        $validator->throw(\InvalidArgumentException::class);
    }
}
