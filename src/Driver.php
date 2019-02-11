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
        'characters.set' => 'utf8mb4',
        'characters.collate' => null,
        'compression.enable' => !true,
        'tls.context' => array(),
        'tls.force' => true,
        'tls.forceLocal' => false
    );
    
    /**
     * @var string[]
     */
    protected $allowedSchemes = array('mysql', 'tcp', 'tls', 'unix');
    
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
     * @var \React\Promise\Promise|null
     */
    protected $connectPromise;
    
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
     * @var \React\Promise\Deferred
     */
    protected $goingAway;
    
    /**
     * @var string|null
     */
    protected $charset;
    
    /**
     * Constructor.
     * @param \React\EventLoop\LoopInterface  $loop
     * @param array                           $options
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
     * Destructor.
     * @return void
     * @internal
     */
    function __destruct() {
        $this->close();
    }
    
    /**
     * Returns the event loop.
     * @return \React\EventLoop\LoopInterface
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
     * @param string  $uri
     * @return \React\Promise\PromiseInterface
     */
    function connect(string $uri): \React\Promise\PromiseInterface {
        if($this->goingAway || $this->connectionState === \Plasma\DriverInterface::CONNECTION_UNUSABLE) {
            return \React\Promise\reject((new \Plasma\Exception('Connection is going away')));
        } elseif($this->connectionState === \Plasma\DriverInterface::CONNECTION_OK) {
            return \React\Promise\resolve();
        } elseif($this->connectPromise !== null) {
            return $this->connectPromise;
        }
        
        $pos = \strpos($uri, '://');
        $unix = false;
        
        if($pos === false) {
            $uri = 'tcp://'.$uri;
        } elseif(\substr($uri, 0, $pos) === 'unix') {
            $defaultSocket = '/tmp/mysql.sock';
            
            $apos = \strpos($uri, '@');
            $spos = \strrpos($uri, '/');
            
            if($apos !== false) {
                $socket = \substr($uri, ($apos + 1), ($spos - ($apos + 1)));
                $uri = \substr($uri, 0, ($apos + 1)).'localhost'.\substr($uri, $spos);
            } else {
                $socket = \substr($uri, ($pos + 3), ($spos - ($pos + 3)));
                $uri = 'unix://localhost'.\substr($uri, $spos);
            }
            
            if($socket === 'localhost' || $socket === '127.0.0.1' || $socket === '::1') {
                $socket = $defaultSocket;
            }
        }
        
        $parts = \parse_url($uri);
        if(!isset($parts['scheme']) || !isset($parts['host']) || !\in_array($parts['scheme'], $this->allowedSchemes)) {
            return \React\Promise\reject((new \InvalidArgumentException('Invalid connect uri given')));
        }
        
        if(isset($socket)) {
            $parts['host'] = $socket;
        }
        
        if($parts['scheme'] === 'mysql') {
            $parts['scheme'] = 'tcp';
        }
        
        $host = $parts['scheme'].'://'.$parts['host'].($parts['scheme'] !== 'unix' ? ':'.($parts['port'] ?? 3306) : '');
        $this->connectionState = static::CONNECTION_STARTED;
        $resolved = false;
        
        $this->connectPromise = $this->connector->connect($host)->then(function (\React\Socket\ConnectionInterface $connection) use ($parts, &$resolved) {
            $this->connectPromise = null;
            
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
            
            $deferred = new \React\Promise\Deferred();
            $this->parser = new \Plasma\Drivers\MySQL\ProtocolParser($this, $this->connection);
            
            $this->parser->on('error', function (\Throwable $error) use (&$deferred, &$resolved) {
                if($resolved) {
                    $this->emit('error', array($error));
                } else {
                    $deferred->reject($error);
                }
            });
            
            $user = ($parts['user'] ?? 'root');
            $password = ($parts['pass'] ?? '');
            $db = \ltrim(($parts['path'] ?? ''), '/');
            
            $credentials = \compact('user', 'password', 'db');
            
            $this->startHandshake($credentials, $deferred);
            return $deferred->promise()->then(function () use (&$resolved) {
                $this->busy = static::STATE_IDLE;
                $resolved = true;
                
                if(\count($this->queue) > 0) {
                    $this->parser->invokeCommand($this->getNextCommand());
                }
            });
        });
        
        if($this->options['characters.set']) {
            $this->charset = $this->options['characters.set'];
            $this->connectPromise = $this->connectPromise->then(function () {
                $query = 'SET NAMES "'.$this->options['characters.set'].'"'
                    .($this->options['characters.collate'] ? ' COLLATE "'.$this->options['characters.collate'].'"' : '');
                
                $cmd = new \Plasma\Drivers\MySQL\Commands\QueryCommand($this, $query);
                $this->executeCommand($cmd);
                
                return $cmd->getPromise();
            });
        }
        
        return $this->connectPromise;
    }
    
    /**
     * Pauses the underlying stream I/O consumption.
     * If consumption is already paused, this will do nothing.
     * @return bool  Whether the operation was successful.
     */
    function pauseStreamConsumption(): bool {
        if($this->connection === null || $this->goingAway) {
            return false;
        }
        
        $this->connection->pause();
        return true;
    }
    
    /**
     * Resumes the underlying stream I/O consumption.
     * If consumption is not paused, this will do nothing.
     * @return bool  Whether the operation was successful.
     */
    function resumeStreamConsumption(): bool {
        if($this->connection === null || $this->goingAway) {
            return false;
        }
        
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
        
        $state = $this->connectionState;
        $this->connectionState = \Plasma\DriverInterface::CONNECTION_UNUSABLE;
        
        // Connection is still pending
        if($this->connectPromise !== null) {
            $this->connectPromise->cancel();
            
            /** @var \Plasma\Drivers\MySQL\Commands\CommandInterface  $command */
            while($command = \array_shift($this->queue)) {
                $command->emit('error', array((new \Plasma\Exception('Connection is going away'))));
            }
        }
        
        $this->goingAway = new \React\Promise\Deferred();
        
        if(\count($this->queue) === 0 || $state < \Plasma\DriverInterface::CONNECTION_OK) {
            $this->queue = array();
            $this->goingAway->resolve();
        }
        
        return $this->goingAway->promise()->then(function () use ($state) {
            if($state !== static::CONNECTION_OK) {
                return;
            } elseif(!$this->connection->isWritable()) {
                $this->connection->close();
                return;
            }
            
            $deferred = new \React\Promise\Deferred();
            
            $quit = new \Plasma\Drivers\MySQL\Commands\QuitCommand();
            
            $this->connection->once('close', function () use (&$deferred) {
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
        if($this->goingAway === null) {
            $this->goingAway = new \React\Promise\Deferred();
        }
        
        $state = $this->connectionState;
        $this->connectionState = \Plasma\DriverInterface::CONNECTION_UNUSABLE;
        
        /** @var \Plasma\Drivers\MySQL\Commands\CommandInterface  $command */
        while($command = \array_shift($this->queue)) {
            $command->emit('error', array((new \Plasma\Exception('Connection is going away'))));
        }
        
        if($this->connectPromise !== null) {
            $this->connectPromise->cancel();
        }
        
        if($state === static::CONNECTION_OK) {
            $quit = new \Plasma\Drivers\MySQL\Commands\QuitCommand();
            $this->parser->invokeCommand($quit);
            
            $this->connection->close();
        }
        
        $this->goingAway->resolve();
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
        if($this->goingAway) {
            return \React\Promise\reject((new \Plasma\Exception('Connection is going away')));
        } elseif($this->connectionState !== \Plasma\DriverInterface::CONNECTION_OK) {
            if($this->connectPromise !== null) {
                return $this->connectPromise->then(function () use (&$client, &$query) {
                    return $this->query($client, $query);
                });
            }
            
            throw new \Plasma\Exception('Unable to continue without connection');
        }
        
        $command = new \Plasma\Drivers\MySQL\Commands\QueryCommand($this, $query);
        $this->executeCommand($command);
        
        if(!$this->transaction) {
            $command->once('end', function () use (&$client) {
                $client->checkinConnection($this);
            });
        }
        
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
        if($this->goingAway) {
            return \React\Promise\reject((new \Plasma\Exception('Connection is going away')));
        } elseif($this->connectionState !== \Plasma\DriverInterface::CONNECTION_OK) {
            if($this->connectPromise !== null) {
                return $this->connectPromise->then(function () use (&$client, &$query) {
                    return $this->prepare($client, $query);
                });
            }
            
            throw new \Plasma\Exception('Unable to continue without connection');
        }
        
        $command = new \Plasma\Drivers\MySQL\Commands\StatementPrepareCommand($client, $this, $query);
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
        if($this->goingAway) {
            return \React\Promise\reject((new \Plasma\Exception('Connection is going away')));
        } elseif($this->connectionState !== \Plasma\DriverInterface::CONNECTION_OK) {
            if($this->connectPromise !== null) {
                return $this->connectPromise->then(function () use (&$client, &$query, $params) {
                    return $this->execute($client, $query, $params);
                });
            }
            
            throw new \Plasma\Exception('Unable to continue without connection');
        }
        
        return $this->prepare($client, $query)->then(function (\Plasma\StatementInterface $statement) use ($params) {
            return $statement->execute($params)->then(function (\Plasma\QueryResultInterface $result) use (&$statement) {
                if($result instanceof \Plasma\StreamQueryResultInterface) {
                    $statement->close()->then(null, function (\Throwable $error) {
                        $this->emit('error', array($error));
                    });
                    
                    return $result;
                }
                
                return $statement->close()->then(function () use ($result) {
                    return $result;
                });
            }, function (\Throwable $error) use (&$statement) {
                return $statement->close()->then(function () use ($error) {
                    throw $error;
                });
            });
        });
    }
    
    /**
     * Quotes the string for use in the query.
     * @param string  $str
     * @param int     $type  For types, see the constants.
     * @return string
     * @throws \LogicException  Thrown if the driver does not support quoting.
     * @throws \Plasma\Exception
     */
    function quote(string $str, int $type = \Plasma\DriverInterface::QUOTE_TYPE_VALUE): string {
        if($this->parser === null) {
            throw new \Plasma\Exception('Unable to continue without connection');
        }
        
        $message = $this->parser->getLastOkMessage();
        if($message === null) {
            $message = $this->parser->getHandshakeMessage();
            
            if($message === null) {
                throw new \Plasma\Exception('Unable to quote without a previous handshake');
            }
        }
        
        $pos = \strpos($this->charset, '_');
        $dbCharset = \substr($this->charset, 0, ($pos !== false ? $pos : \strlen($this->charset)));
        $realCharset = $this->getRealCharset($dbCharset);
        
        if(($message->statusFlags & \Plasma\Drivers\MySQL\StatusFlags::SERVER_STATUS_NO_BACKSLASH_ESCAPES) !== 0) {
            return $this->escapeUsingQuotes($realCharset, $str, $type);
        }
        
        return $this->escapeUsingBackslashes($realCharset, $str, $type);
    }
    
    /**
     * Escapes using quotes.
     * @param string  $realCharset
     * @param string  $str
     * @param int     $type
     * @return string
     */
    function escapeUsingQuotes(string $realCharset, string $str, int $type): string {
        if($type === \Plasma\DriverInterface::QUOTE_TYPE_IDENTIFIER) {
            return '`'.\str_replace('`', '``', $str).'`';
        }
        
        $escapeChars = array(
            '"',
            '\\',
        );
        
        $escapeReplace = array(
            '""',
            '\\\\',
        );
        
        return '"'.\str_replace($escapeChars, $escapeReplace, $str).'"';
    }
    
    /**
     * Escapes using backslashes.
     * @param string  $realCharset
     * @param string  $str
     * @param int     $type
     * @return string
     */
    function escapeUsingBackslashes(string $realCharset, string $str, int $type): string {
        if($type === \Plasma\DriverInterface::QUOTE_TYPE_IDENTIFIER) {
            return '`'.\str_replace('`', '\\`', $str).'`';
        }
        
        $escapeChars = array(
            '\\',
            '"',
        );
        
        $escapeReplace = array(
            '\\\\',
            '\"',
        );
        
        return '"'.\str_replace($escapeChars, $escapeReplace, $str).'"';
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
        if($this->goingAway) {
            return \React\Promise\reject((new \Plasma\Exception('Connection is going away')));
        }
        
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
        
        $this->transaction = true;
        
        return $this->query($client, $query)->then(function () use (&$client) {
            return $this->query($client, 'START TRANSACTION');
        })->then(function () use (&$client, $isolation) {
            return (new \Plasma\Transaction($client, $this, $isolation));
        })->then(null, function (\Throwable $e) {
            $this->transaction = false;
            throw $e;
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
        if($this->goingAway) {
            return \React\Promise\reject((new \Plasma\Exception('Connection is going away')));
        }
        
        return (new \React\Promise\Promise(function (callable $resolve, callable $reject) use (&$client, &$command) {
            $command->once('end', function ($value = null) use (&$client, &$resolve) {
                if(!$this->transaction) {
                    $client->checkinConnection($this);
                }
                
                $resolve($value);
            });
            
            $command->once('error', function (\Throwable $error) use (&$client, &$reject) {
                if(!$this->transaction) {
                    $client->checkinConnection($this);
                }
                
                $reject($error);
            });
            
            $this->executeCommand($command);
        }));
    }
    
    /**
     * Runs the given SQL querybuilder.
     * The driver CAN throw an exception if the given querybuilder is not supported.
     * An example would be a SQL querybuilder and a Cassandra driver.
     * @param \Plasma\ClientInterface           $client
     * @param \Plasma\SQLQuerybuilderInterface  $query
     * @return \React\Promise\PromiseInterface
     * @throws \Plasma\Exception
     */
    function runQuery(\Plasma\ClientInterface $client, \Plasma\QuerybuilderInterface $query): \React\Promise\PromiseInterface {
        if($this->goingAway) {
            return \React\Promise\reject((new \Plasma\Exception('Connection is going away')));
        }
        
        if(!($query instanceof \Plasma\SQLQuerybuilderInterface)) {
            throw new \Plasma\Exception('Given querybuilder must be a SQL querybuilder');
        }
        
        $sql = $query->getQuery();
        $params = $query->getParameters();
        
        return $this->execute($client, $sql, $params);
    }
    
    /**
     * Executes a command.
     * @param \Plasma\CommandInterface  $command
     * @return void
     * @internal
     */
    function executeCommand(\Plasma\CommandInterface $command): void {
        $this->queue[] = $command;
        
        if($this->parser && $this->busy === static::STATE_IDLE) {
            $this->parser->invokeCommand($this->getNextCommand());
        }
    }
    
    /**
     * Get the handshake message, or null if none received yet.
     * @return \Plasma\Drivers\MySQL\Messages\HandshakeMessage|null
     */
    function getHandshake(): ?\Plasma\Drivers\MySQL\Messages\HandshakeMessage {
        if($this->parser) {
            return $this->parser->getHandshakeMessage();
        }
        
        return null;
    }
    
    /**
     * Get the next command, or null.
     * @return \Plasma\CommandInterface|null
     * @internal
     */
    function getNextCommand(): ?\Plasma\CommandInterface {
        if(\count($this->queue) === 0) {
            if($this->goingAway) {
                $this->goingAway->resolve();
            }
            
            return null;
        } elseif($this->busy === static::STATE_BUSY) {
            return null;
        }
        
        /** @var \Plasma\CommandInterface  $command */
        $command =  \array_shift($this->queue);
        
        if($command->waitForCompletion()) {
            $this->busy = static::STATE_BUSY;
            
            $command->once('error', function () use (&$command) {
                $this->busy = static::STATE_IDLE;
                
                $this->endCommand();
            });
            
            $command->once('end', function () use (&$command) {
                $this->busy = static::STATE_IDLE;
                
                $this->endCommand();
            });
        } else {
            $this->endCommand();
        }
        
        return $command;
    }
    
    /**
     * Finishes up a command.
     * @return void
     */
    protected function endCommand() {
        $this->loop->futureTick(function () {
            if($this->goingAway && \count($this->queue) === 0) {
                return $this->goingAway->resolve();
            }
            
            $this->parser->invokeCommand($this->getNextCommand());
        });
    }
    
    /**
     * Starts the handshake process.
     * @param array                    $credentials
     * @param \React\Promise\Deferred  $deferred
     * @return void
     */
    protected function startHandshake(array $credentials, \React\Promise\Deferred $deferred) {
        $listener = function (\Plasma\Drivers\MySQL\Messages\MessageInterface $message) use ($credentials, &$deferred, &$listener) {
            if($message instanceof \Plasma\Drivers\MySQL\Messages\HandshakeMessage) {
                $this->parser->removeListener('message', $listener);
                
                $this->connectionState = static::CONNECTION_SETENV;
                $clientFlags = \Plasma\Drivers\MySQL\ProtocolParser::CLIENT_CAPABILITIES;
                
                \extract($credentials);
                
                if($db !== '') {
                    $clientFlags |= \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_CONNECT_WITH_DB;
                }
                
                if($this->charset === null) {
                    $this->charset = \Plasma\Drivers\MySQL\CharacterSetFlags::CHARSET_MAP[$message->characterSet] ?? 'latin1';
                }
                
                if(($message->capability & \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_COMPRESS) !== 0 && \extension_loaded('zlib') && $this->options['compression.enable']) {
                    $this->parser->enableCompression();
                    $clientFlags |= \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_COMPRESS;
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
                
                $remote = \parse_url($this->connection->getRemoteAddress());
                
                if($remote !== false && ($remote['host'] !== '127.0.0.1' || $this->options['tls.forceLocal'])) {
                    if(($message->capability & \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_SSL) !== 0) { // If SSL supported, connect through SSL
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
                    } elseif($this->options['tls.force'] || $this->options['tls.forceLocal']) {
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
        
        return $this->encryption->enable($this->connection)->then(null, function (\Throwable $error) {
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
            $this->loop->futureTick(function () use (&$deferred) {
                $deferred->resolve();
            });
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
                        $plugins = \Plasma\Drivers\MySQL\DriverFactory::getAuthPlugins();
                        foreach($plugins as $key => $plug) {
                            if($key === $name) {
                                $plugin = new $plug($this->parser, $this->parser->getHandshakeMessage());
                                
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
     * Get the real charset from the DB charset.
     * @param string  $charset
     * @return string
     */
    protected function getRealCharset(string $charset): string {
        if(\substr($charset, 0, 4) === 'utf8') {
            return 'UTF-8';
        }
        
        $charsets = \mb_list_encodings();
        
        foreach($charsets as $set) {
            if(\stripos($set, $charset) === 0) {
                return $set;
            }
        }
        
        return 'UTF-8';
    }
    
    /**
     * Validates the given options.
     * @param array  $options
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function validateOptions(array $options) {
        \CharlotteDunois\Validation\Validator::make($options, array(
            'characters.set' => 'string',
            'characters.collate' => 'string',
            'compression.enable' => 'boolean',
            'connector' => 'class:'.\React\Socket\ConnectorInterface::class.'=object',
            'packet.maxAllowedSize' => 'integer|min:0|max:'.\Plasma\Drivers\MySQL\ProtocolParser::CLIENT_MAX_PACKET_SIZE,
            'tls.context' => 'array',
            'tls.force' => 'boolean',
            'tls.forceLocal' => 'boolean'
        ), true)->throw(\InvalidArgumentException::class);
    }
}
