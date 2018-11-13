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
    protected $options = array(
        'tls.context' => array(),
        'tls.force' => false,
        'tls.ignoreIPs' => array(
            'localhost',
            '127.0.0.1'
        )
    );
    
    /**
     * @var \React\Socket\ConnectorInterface
     */
    protected $connector;
    
    /**
     * Internal class is intentional used, as there's no other way currently.
     * @var \React\Socket\StreamEncryption
     * @see https://github.com/reactphp/socket/issues/180
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
     * @var \Plasma\Drivers\MySQL\ProtocolParser
     */
    protected $parser;
    
    /**
     * @var array
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
        $this->validateOptions($options);
        
        $this->loop = $loop;
        $this->options = \array_merge($this->options, $options);
        
        $this->connector = ($options['connector'] ?? (new \React\Socket\Connector($loop)));
        $this->encryption = new \React\Socket\StreamEncryption($this->loop, false);
        $this->queue = array();
    }
    
    /**
     * Returns the event loop.
     * @var \React\EventLoop\LoopInterface
     */
    function getLoop(): \React\EventLoop\LoopInterface {
        return $this->loop;
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
        return \count($this->queue);
    }
    
    /**
     * Connects to the given URI.
     * @return \React\Promise\PromiseInterface
     */
    function connect(string $uri): \React\Promise\PromiseInterface {
        $uri = 'mysql://'.ltrim($uri, 'mysql://');
        
        $parts = \parse_url($uri);
        if(!isset($parts['host'])) {
            return \React\Promise\reject((new \InvalidArgumentException('Invalid connect uri given')));
        }
        
        $host = $parts['host'].':'.($parts['port'] ?? 3306);
        $this->connectionState = static::CONNECTION_STARTED;
        
        return $this->connector->connect($host)->then(function (\React\Socket\ConnectionInterface $connection) use ($parts) {
            // See description of property encryption
            if(!($connection instanceof \React\Socket\Connection)) {
                throw new \LogicException('Custom connection class is NOT supported yet (encryption limitation)');
            }
            
            $this->busy = static::STATE_BUSY;
            $this->connectionState = static::CONNECTION_MADE;
            $this->connection = $connection;
            
            $this->connection->on('error', function (\Throwable $error) {
                $this->emit('error', array($error));
            });
            
            $this->connection->on('close', function () {
                $this->connection = null;
                $this->connectionState = static::CONNECTION_UNUSABLE;
                
                $this->emit('close');
            });
            
            $this->parser = new \Plasma\Drivers\MySQL\ProtocolParser($this, $this->connection);
            
            $user = ($parts['user'] ?? 'root');
            $password = ($parts['password'] ?? '');
            $db = (!empty($parts['path']) ? \ltrim($parts['path'], '/') : '');
            
            $credentials = \compact('user', 'password', 'db');
            $deferred = new \React\Promise\Deferred();
            
            $this->startHandshake($db, $credentials, $deferred);
            return $deferred->promise();
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
        if($this->goingAway) {
            return $this->goingAway->promise();
        }
        
        $this->goingAway = new \React\Promise\Deferred();
        
        if(\count($this->queue) === 0) {
            $this->goingAway->resolve();
        }
        
        return $this->goingAway->promise()->then(function () {
            $deferred = new \React\Promise\Deferred();
            
            $quit = new \Plasma\Drivers\MySQL\Commands\QuitCommand();
            
            $connection->once('close', function () use (&$deferred) {
                $deferred->resolve();
            });
            
            $quit->once('end', function () {
                $this->connection->close();
            });
            
            $this->parser->invokeCommand($quit);
            
            return $deferred->promise();
        });
    }
    
    /**
     * Forcefully closes the connection, without waiting for any outstanding requests. This will reject all outstanding requests.
     * @return void
     */
    function quit(): void {
        /** @var \Plasma\Drivers\MySQL\Commands\CommandInterface  $command */
        while($command = \array_shift($this->queue)) {
            $command->emit('error', array((new \Plasma\Exception('Connection is going away'))));
        }
        
        $quit = new \Plasma\Drivers\MySQL\Commands\QuitCommand();
        $this->parser->invokeCommand($quit);
        
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
     * Executes a plain query. Resolves with a `QueryResultInterface` instance.
     * When the command is done, the driver must check itself back into the client.
     * @param \Plasma\ClientInterface  $client
     * @param string                   $query
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\Exception
     * @see \Plasma\QueryResultInterface
     */
    function query(\Plasma\ClientInterface $client, string $query): \React\Promise\PromiseInterface {
        $command = new \Plasma\Drivers\MySQL\Commands\QueryCommand($this, $query);
        $this->executeCommand($command);
        
        $command->once('end', function () use (&$client) {
            $client->checkinConnection($this);
        });
        
        return $command->getPromise();
    }
    
    /**
     * Prepares a query. Resolves with a `StatementInterface` instance.
     * When the command is done, the driver must check itself back into the client.
     * @param \Plasma\ClientInterface  $client
     * @param string                   $query
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\Exception
     * @see \Plasma\StatementInterface
     */
    function prepare(\Plasma\ClientInterface $client, string $query): \React\Promise\PromiseInterface {
        $command = new \Plasma\Drivers\MySQL\Commands\PrepareCommand($client, $this, $query);
        $this->executeCommand($command);
        
        return $command->getPromise();
    }
    
    /**
     * Prepares and executes a query. Resolves with a `QueryResultInterface` instance.
     * This is equivalent to prepare -> execute -> close.
     * If you need to execute a query multiple times, prepare the query manually for performance reasons.
     * @param \Plasma\ClientInterface  $client
     * @param string                   $query
     * @param array                    $params
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\Exception
     * @see \Plasma\StatementInterface
     */
    function execute(\Plasma\ClientInterface $client, string $query, array $params = array()): \React\Promise\PromiseInterface {
        return $this->prepare($client, $query)->then(function (\Plasma\StatementInterface $statement) use ($params) {
            return $statement->execute($params)->then(function (\Plasma\QueryResultInterface $result) use (&$statement) {
                return $statement->close()->then(function () use ($result) {
                    return $result;
                });
            }, function (\Throwable $error) use (&$statement) {
                return $statement->close()->then(function () use ($error) {
                    throw $error;
                });
            });
        })->always(function () use (&$client) {
            $client->checkinConnection($this);
        });
    }
    
    /**
     * Quotes the string for use in the query.
     * @param string  $str
     * @return string
     * @throws \LogicException  Thrown if the driver does not support quoting.
     * @throws \Plasma\Exception
     */
    function quote(string $str): string { // TODO
        throw new \LogicException('Not implemented yet');
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
     * Runs the given command.
     * Returns a Promise, which resolves with the `end` event argument (defaults to `null),
     * or rejects with the `Throwable` of the `error` event.
     * When the command is done, the driver must check itself back into the client.
     * @param \Plasma\ClientInterface   $client
     * @param \Plasma\CommandInterface  $command
     * @return \React\Promise\PromiseInterface
     */
    function runCommand(\Plasma\ClientInterface $client, \Plasma\CommandInterface $command) {
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use (&$client, &$command) {
            $command->once('end', function ($value = null) use (&$client, &$resolve) {
                $client->checkinConnection($this);
                $resolve($value);
            });
            
            $command->once('error', function (\Throwable $error) use (&$client, &$reject) {
                $client->checkinConnection($this);
                $reject($error);
            });
            
            $this->executeCommand($command);
        }));
    }
    
    /**
     * Executes a command.
     * @param \Plasma\CommandInterface  $command
     * @return void
     */
    function executeCommand(\Plasma\CommandInterface $command): void {
        $this->queue[] = $command;
        
        if($this->parser && $this->parser->getCurrentCommand() === null) {
            $this->parser->invokeCommand($this->getNextCommand());
        }
    }
    
    /**
     * Get the next command, or null.
     * @return \Plasma\CommandInterface|null
     */
    function getNextCommand(): ?\Plasma\CommandInterface {
        if(\count($this->queue) === 0) {
            if($this->goingAway) {
                $this->goingAway->resolve();
            }
            
            return null;
        }
        
        /** @var \Plasma\CommandInterface  $command */
        $command =  \array_shift($this->queue);
        
        if($command->waitForCompletion()) {
            $this->busy = static::STATE_BUSY;
        }
        
        $command->once('end', function () {
            $this->busy = static::STATE_IDLE;
            
            if($this->goingAway && \count($this->queue) === 0) {
                return $this->goingAway->resolve();
            }
            
            $this->parser->invokeCommand($this->getNextCommand());
        });
        
        return $command;
    }
    
    /**
     * Starts the handshake process.
     * @param string                   $db
     * @param array                    $credentials
     * @param \React\Promise\Deferred  $deferred
     * @return void
     */
    protected function startHandshake(string $db, array $credentials, \React\Promise\Deferred $deferred) {
        $listener = function (\Plasma\Drivers\MySQL\Messages\MessageInterface $message) use ($db, $credentials, &$deferred, &$listener) {
            if($message instanceof \Plasma\Drivers\MySQL\Messages\HandshakeMessage) {
                $this->parser->removeListener('message', $listener);
                
                $this->connectionState = static::CONNECTION_SETENV;
                $clientFlags = \Plasma\Drivers\MySQL\ProtocolParser::CLIENT_CAPABILITIES;
                
                if($db !== '') {
                    $clientFlags |= \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_CONNECT_WITH_DB;
                }
                
                // Check if we support auth plugins
                $plugins = \Plasma\Drivers\MySQL\DriverFactory::getAuthPlugins();
                $plugin = null;
                
                foreach($plugins as $key => $plug) {
                    if(\is_int($key) && ($message->capability & $key) !== 0) {
                        $plugin = $plug;
                        $clientFlags |= \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_PLUGIN_AUTH;
                        break;
                    } elseif($key === $message->authPluginName) {
                        $plugin = $plug;
                        $clientFlags |= \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_PLUGIN_AUTH;
                        break;
                    }
                }
                
                $remote = $this->connection->getRemoteAddress();
                $ignored = \in_array($remote, $this->options['tls.ignoreIPs']);
                
                // If SSL supported, connect through SSL
                if(!$ignored && ($message->capability & \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_SSL) !== 0) {
                    $clientFlags |= \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_SSL;
                    
                    $ssl = new \Plasma\Drivers\MySQL\Commands\SSLRequestCommand($message, $clientFlags);
                    
                    $ssl->once('end', function () use ($credentials, $clientFlags, $plugin, &$deferred, &$message) {
                        $this->connectionState = static::CONNECTION_SSL_STARTUP;
                        
                        $this->enableTLS()->then(function () use ($credentials, $clientFlags, $plugin, &$deferred, &$message) {
                            $this->createHandshakeResponse($message, $credentials, $clientFlags, $plugin, $deferred);
                        }, function (\Throwable $error) use (&$deferred) {
                            $deferred->reject($$error);
                            $this->connection->close();
                        });
                    });
                    
                    return $this->parser->invokeCommand($ssl);
                } else {
                    $ipCheck = (\filter_var($remote, \FILTER_VALIDATE_IP) === false ||
                        \filter_var($remote, \FILTER_VALIDATE_IP, \FILTER_FLAG_NO_PRIV_RANGE) === false);
                    
                    if(!$ignored && $ipCheck && $this->options['tls.force']) {
                        $deferred->reject((new \Plasma\Exception('TLS is not supported by the server')));
                        $this->connection->close();
                        return;
                    }
                }
                
                $this->createHandshakeResponse($message, $credentials, $clientFlags, $plugin, $deferred);
            }
        };
        
        $this->parser->on('message', $listener);
        
        $this->parser->on('message', function (\Plasma\Drivers\MySQL\Messages\MessageInterface $message) {
            if($message instanceof \Plasma\Drivers\MySQL\Messages\OkResponseMessage) {
                $this->connectionState = static::CONNECTION_OK;
            }
            
            $this->emit('eventRelay', array('message', $message));
        });
    }
    
    /**
     * Enables TLS on the connection.
     * @return \React\Promise\PromiseInterface
     */
    protected function enableTLS(): \React\Promise\PromiseInterface {
        // Set required SSL/TLS context options
        foreach($this->options['tls.context'] as $name => $value) {
            \stream_context_set_option($this->connection->stream, 'ssl', $name, $value);
        }
        
        return $this->encryption->enable($this->connection)->otherwise(function (\Throwable $error) {
            $this->connection->close();
            throw new \RuntimeException('Connection failed during TLS handshake: '.$error->getMessage(), $error->getCode());
        });
    }
    
    /**
     * Sends the auth command.
     * @param \Plasma\Drivers\MySQL\Messages\HandshakeMessage  $message
     * @param array                                            $credentials
     * @param int                                              $clientFlags
     * @param string|null                                      $plugin
     * @param \React\Promise\Deferred                          $deferred
     * @return void
     */
    protected function createHandshakeResponse(
        \Plasma\Drivers\MySQL\Messages\HandshakeMessage $message, array $credentials, int $clientFlags, ?string $plugin, \React\Promise\Deferred $deferred
    ) {
        \extract($credentials);
        
        $auth = new \Plasma\Drivers\MySQL\Commands\HandshakeResponseCommand($this->parser, $message, $clientFlags, $plugin, $user, $password, $db);
        
        $auth->once('end', function () use (&$deferred) {
            $deferred->resolve();
        });
        
        $auth->once('error', function (\Throwable $error) use (&$deferred) {
            $deferred->reject($error);
            $this->connection->close();
        });
        
        if($plugin) {
            $listener = function (\Plasma\Drivers\MySQL\Messages\MessageInterface $message) use ($password, &$deferred, &$listener) {
                /** @var \Plasma\Drivers\MySQL\AuthPlugins\AuthPluginInterface|null  $plugin */
                static $plugin;
                
                if($message instanceof \Plasma\Drivers\MySQL\Messages\AuthSwitchRequestMessage) {
                    $name = $message->authPluginName;
                    
                    if($name !== null) {
                        foreach($plugins as $key => $plug) {
                            if($key === $name) {
                                $plugin = new $plug($this->parser, $message);
                                
                                $command = new \Plasma\Drivers\MySQL\Commands\AuthSwitchResponseCommand($message, $plugin, $password);
                                return $this->parser->invokeCommand($command);
                            }
                        }
                    }
                    
                    $deferred->reject((new \Plasma\Exception('Requested authentication method '.($name ? '"'.$name.'" ' : '').'is not supported')));
                } elseif($message instanceof \Plasma\Drivers\MySQL\Messages\AuthMoreDataMessage) {
                    if($plugin === null) {
                        $deferred->reject((new \Plasma\Exception('No auth plugin is in use, but we received auth more data packet')));
                        return $this->connection->close();
                    }
                    
                    try {
                        $command = $plugin->receiveMoreData($message);
                        return $this->parser->invokeCommand($command);
                    } catch (\Plasma\Exception $e) {
                        $deferred->reject($e);
                        $this->connection->close();
                    }
                } elseif($message instanceof \Plasma\Drivers\MySQL\Messages\OkResponseMessage) {
                    $this->parser->removeListener('message', $listener);
                }
            };
            
            $this->parser->on('message', $listener);
        }
        
        $this->parser->invokeCommand($auth);
        $this->connectionState = static::CONNECTION_AWAITING_RESPONSE;
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
            'tls.context' => 'array',
            'tls.force' => 'boolean'
        ));
        
        $validator->throw(\InvalidArgumentException::class);
    }
}
