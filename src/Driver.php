<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
 */

namespace Plasma\Drivers\MySQL;

use Evenement\EventEmitterTrait;
use Obsidian\Validation\Validator;
use Plasma\ClientInterface;
use Plasma\CommandInterface;
use Plasma\DriverInterface;
use Plasma\Drivers\MySQL\AuthPlugins\AuthPluginInterface;
use Plasma\Drivers\MySQL\Commands\AuthSwitchResponseCommand;
use Plasma\Drivers\MySQL\Commands\HandshakeResponseCommand;
use Plasma\Drivers\MySQL\Commands\QueryCommand;
use Plasma\Drivers\MySQL\Commands\QuitCommand;
use Plasma\Drivers\MySQL\Commands\SSLRequestCommand;
use Plasma\Drivers\MySQL\Commands\StatementPrepareCommand;
use Plasma\Drivers\MySQL\Messages\AuthMoreDataMessage;
use Plasma\Drivers\MySQL\Messages\AuthSwitchRequestMessage;
use Plasma\Drivers\MySQL\Messages\HandshakeMessage;
use Plasma\Drivers\MySQL\Messages\MessageInterface;
use Plasma\Drivers\MySQL\Messages\OkResponseMessage;
use Plasma\Exception;
use Plasma\QueryBuilderInterface;
use Plasma\QueryResultInterface;
use Plasma\SQLQueryBuilderInterface;
use Plasma\StatementInterface;
use Plasma\StreamQueryResultInterface;
use Plasma\Transaction;
use Plasma\TransactionInterface;
use React\EventLoop\LoopInterface;
use React\Promise;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use React\Socket\Connection;
use React\Socket\ConnectionInterface;
use React\Socket\Connector;
use React\Socket\ConnectorInterface;
use React\Socket\StreamEncryption;

/**
 * The MySQL Driver.
 * @internal
 */
class Driver implements DriverInterface {
    use EventEmitterTrait;
    
    /**
     * @var LoopInterface
     */
    protected $loop;
    
    /**
     * @var array
     */
    protected $options = array(
        'characters.set' => 'utf8mb4',
        'characters.collate' => null,
        'compression.enable' => true,
        'localInFile.enable' => false,
        'tls.context' => array(),
        'tls.force' => true,
        'tls.forceLocal' => false
    );
    
    /**
     * @var string[]
     */
    protected $allowedSchemes = array('mysql', 'tcp', 'tls', 'unix');
    
    /**
     * @var ConnectorInterface
     */
    protected $connector;
    
    /**
     * Internal class is intentional used, as there's no other way currently.
     * @var StreamEncryption
     * @see https://github.com/reactphp/socket/issues/180
     */
    protected $encryption;
    
    /**
     * @var Promise\Promise|null
     */
    protected $connectPromise;
    
    /**
     * @var Connection
     */
    protected $connection;
    
    /**
     * @var int
     */
    protected $connectionState = DriverInterface::CONNECTION_CLOSED;
    
    /**
     * @var ProtocolParser
     */
    protected $parser;
    
    /**
     * @var array
     */
    protected $queue;
    
    /**
     * @var int
     */
    protected $busy = DriverInterface::STATE_IDLE;
    
    /**
     * @var bool
     */
    protected $transaction = false;
    
    /**
     * @var Deferred
     */
    protected $goingAway;
    
    /**
     * @var string|null
     */
    protected $charset;
    
    /**
     * @var bool|null
     */
    protected $cursorSupported;
    
    /**
     * Constructor.
     * @param LoopInterface  $loop
     * @param array          $options
     */
    function __construct(LoopInterface $loop, array $options) {
        $this->validateOptions($options);
        
        $this->loop = $loop;
        $this->options = \array_merge($this->options, $options);
        
        $this->connector = ($options['connector'] ?? (new Connector($loop)));
        $this->encryption = new StreamEncryption($this->loop, false);
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
     * @return LoopInterface
     */
    function getLoop(): LoopInterface {
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
     * @return PromiseInterface
     * @throws \InvalidArgumentException
     */
    function connect(string $uri): PromiseInterface {
        if($this->goingAway || $this->connectionState === DriverInterface::CONNECTION_UNUSABLE) {
            return Promise\reject((new Exception('Connection is going away')));
        } elseif($this->connectionState === DriverInterface::CONNECTION_OK) {
            return Promise\resolve();
        } elseif($this->connectPromise !== null) {
            return $this->connectPromise;
        }
        
        $pos = \strpos($uri, '://');
        
        if($pos === false) {
            $uri = 'tcp://'.$uri;
        } elseif(\strpos($uri, 'unix') === 0) {
            $defaultSocket = '/tmp/mysql.sock';
            
            $apos = \strpos($uri, '@');
            $spos = \strrpos($uri, '/');
            
            if($apos === false) {
                throw new \InvalidArgumentException('Connecting without any username is not a valid operation');
            }
            
            $socket = \substr($uri, ($apos + 1), ($spos - ($apos + 1)));
            $uri = \substr($uri, 0, ($apos + 1)).'localhost'.\substr($uri, $spos);
            
            if($socket === 'localhost' || $socket === '127.0.0.1' || $socket === '::1') {
                $socket = $defaultSocket;
            }
        }
        
        $parts = \parse_url($uri);
        if(!isset($parts['scheme']) || !isset($parts['host']) || !\in_array($parts['scheme'], $this->allowedSchemes, true)) {
            throw new \InvalidArgumentException('Invalid connect uri given');
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
        
        $this->connectPromise = $this->connector->connect($host)->then(
            function (ConnectionInterface $connection) use ($parts, &$resolved) {
                $this->connectPromise = null;
                
                // See description of property encryption
                if(!($connection instanceof Connection)) {
                    throw new \LogicException('Custom connection class is NOT supported yet (encryption limitation)');
                }
                
                $this->busy = static::STATE_BUSY;
                $this->connectionState = static::CONNECTION_MADE;
                $this->connection = $connection;
                
                $this->connection->on(
                    'error',
                    function (\Throwable $error) {
                        $this->emit('error', array($error));
                    }
                );
                
                $this->connection->on(
                    'close',
                    function () {
                        $this->connection = null;
                        $this->connectionState = static::CONNECTION_UNUSABLE;
                        
                        $this->emit('close');
                    }
                );
                
                $deferred = new Deferred();
                $this->parser = new ProtocolParser($this, $this->connection);
                
                $this->parser->on(
                    'error',
                    function (\Throwable $error) use (&$deferred, &$resolved) {
                        if($resolved) {
                            $this->emit('error', array($error));
                        } else {
                            $deferred->reject($error);
                        }
                    }
                );
                
                $user = ($parts['user'] ?? 'root');
                $password = ($parts['pass'] ?? '');
                $db = \ltrim(($parts['path'] ?? ''), '/');
                
                $credentials = \compact('user', 'password', 'db');
                
                $this->startHandshake($credentials, $deferred);
                return $deferred->promise()->then(
                    function () use (&$resolved) {
                        $this->busy = static::STATE_IDLE;
                        $resolved = true;
                        
                        if(\count($this->queue) > 0) {
                            $this->parser->invokeCommand($this->getNextCommand());
                        }
                    }
                );
            }
        );
        
        if($this->options['characters.set']) {
            $this->charset = $this->options['characters.set'];
            $this->connectPromise = $this->connectPromise->then(
                function () {
                    $query = 'SET NAMES "'.$this->options['characters.set'].'"'
                        .($this->options['characters.collate'] ? ' COLLATE "'.$this->options['characters.collate'].'"' : '');
                    
                    $cmd = new QueryCommand($this, $query);
                    $this->executeCommand($cmd);
                    
                    return $cmd->getPromise();
                }
            );
        }
        
        return $this->connectPromise;
    }
    
    /**
     * Closes all connections gracefully after processing all outstanding requests.
     * @return PromiseInterface
     */
    function close(): PromiseInterface {
        if($this->goingAway) {
            return $this->goingAway->promise();
        }
        
        $state = $this->connectionState;
        $this->connectionState = DriverInterface::CONNECTION_UNUSABLE;
        
        // Connection is still pending
        if($this->connectPromise !== null) {
            $this->connectPromise->cancel();
            
            /** @var Commands\CommandInterface $command */
            while($command = \array_shift($this->queue)) {
                $command->emit('error', array((new Exception('Connection is going away'))));
            }
        }
        
        $this->goingAway = new Deferred();
        
        if($state < DriverInterface::CONNECTION_OK || \count($this->queue) === 0) {
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
            
            $deferred = new Deferred();
            
            $quit = new QuitCommand();
            
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
            $this->goingAway = new Deferred();
        }
        
        $state = $this->connectionState;
        $this->connectionState = DriverInterface::CONNECTION_UNUSABLE;
        
        /** @var Commands\CommandInterface $command */
        while($command = \array_shift($this->queue)) {
            $command->emit('error', array((new Exception('Connection is going away'))));
        }
        
        if($this->connectPromise !== null) {
            $this->connectPromise->cancel();
        }
        
        if($state === static::CONNECTION_OK) {
            $quit = new QuitCommand();
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
     * @param ClientInterface  $client
     * @param string           $query
     * @return PromiseInterface
     * @throws Exception
     * @see \Plasma\QueryResultInterface
     */
    function query(ClientInterface $client, string $query): PromiseInterface {
        if($this->goingAway) {
            return Promise\reject((new Exception('Connection is going away')));
        } elseif($this->connectionState !== DriverInterface::CONNECTION_OK) {
            if($this->connectPromise !== null) {
                return $this->connectPromise->then(
                    function () use (&$client, &$query) {
                        return $this->query($client, $query);
                    }
                );
            }
            
            throw new Exception('Unable to continue without connection');
        }
        
        $command = new QueryCommand($this, $query);
        $this->executeCommand($command);
        
        if(!$this->transaction) {
            $command->once(
                'end',
                function () use (&$client) {
                    $client->checkinConnection($this);
                }
            );
        }
        
        return $command->getPromise();
    }
    
    /**
     * Prepares a query. Resolves with a `StatementInterface` instance.
     * When the command is done, the driver must check itself back into the client.
     * @param ClientInterface  $client
     * @param string           $query
     * @return PromiseInterface
     * @throws Exception
     * @see \Plasma\StatementInterface
     */
    function prepare(ClientInterface $client, string $query): PromiseInterface {
        if($this->goingAway) {
            return Promise\reject((new Exception('Connection is going away')));
        } elseif($this->connectionState !== DriverInterface::CONNECTION_OK) {
            if($this->connectPromise !== null) {
                return $this->connectPromise->then(
                    function () use (&$client, &$query) {
                        return $this->prepare($client, $query);
                    }
                );
            }
            
            throw new Exception('Unable to continue without connection');
        }
        
        $command = new StatementPrepareCommand($client, $this, $query);
        $this->executeCommand($command);
        
        return $command->getPromise();
    }
    
    /**
     * Prepares and executes a query. Resolves with a `QueryResultInterface` instance.
     * This is equivalent to prepare -> execute -> close.
     * If you need to execute a query multiple times, prepare the query manually for performance reasons.
     * @param ClientInterface  $client
     * @param string           $query
     * @param array            $params
     * @return PromiseInterface
     * @throws Exception
     * @see \Plasma\StatementInterface
     */
    function execute(ClientInterface $client, string $query, array $params = array()): PromiseInterface {
        if($this->goingAway) {
            return Promise\reject((new Exception('Connection is going away')));
        } elseif($this->connectionState !== DriverInterface::CONNECTION_OK) {
            if($this->connectPromise !== null) {
                return $this->connectPromise->then(
                    function () use (&$client, &$query, $params) {
                        return $this->execute($client, $query, $params);
                    }
                );
            }
            
            throw new Exception('Unable to continue without connection');
        }
        
        return $this->prepare($client, $query)->then(
            function (StatementInterface $statement) use ($params) {
                return $statement->execute($params)->then(
                    function (QueryResultInterface $result) use (&$statement) {
                        if($result instanceof StreamQueryResultInterface) {
                            $statement->close()->then(
                                null,
                                function (\Throwable $error) {
                                    $this->emit('error', array($error));
                                }
                            );
                            
                            return $result;
                        }
                        
                        return $statement->close()->then(
                            function () use ($result) {
                                return $result;
                            }
                        );
                    },
                    function (\Throwable $error) use (&$statement) {
                        return $statement->close()->then(
                            function () use ($error) {
                                throw $error;
                            }
                        );
                    }
                );
            }
        );
    }
    
    /**
     * Quotes the string for use in the query.
     * @param string  $str
     * @param int     $type  For types, see the constants.
     * @return string
     * @throws \LogicException  Thrown if the driver does not support quoting.
     * @throws Exception
     */
    function quote(string $str, int $type = DriverInterface::QUOTE_TYPE_VALUE): string {
        if($this->parser === null) {
            throw new Exception('Unable to continue without connection');
        }
        
        $message = $this->parser->getLastOkMessage();
        if($message === null) {
            $message = $this->parser->getHandshakeMessage();
            
            if($message === null) {
                throw new Exception('Unable to quote without a previous handshake');
            }
        }
        
        if(($message->statusFlags & StatusFlags::SERVER_STATUS_NO_BACKSLASH_ESCAPES) !== 0) {
            return $this->escapeUsingQuotes('', $str, $type);
        }
        
        return $this->escapeUsingBackslashes('', $str, $type);
    }
    
    /**
     * Escapes using quotes.
     * @param string  $realCharset
     * @param string  $str
     * @param int     $type
     * @return string
     * @noinspection PhpUnusedParameterInspection
     */
    function escapeUsingQuotes(string $realCharset, string $str, int $type): string {
        if($type === DriverInterface::QUOTE_TYPE_IDENTIFIER) {
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
     * @noinspection PhpUnusedParameterInspection
     */
    function escapeUsingBackslashes(string $realCharset, string $str, int $type): string {
        if($type === DriverInterface::QUOTE_TYPE_IDENTIFIER) {
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
     * @param ClientInterface  $client
     * @param int              $isolation  See the `TransactionInterface` constants.
     * @return PromiseInterface
     * @throws Exception
     * @see \Plasma\TransactionInterface
     */
    function beginTransaction(
        ClientInterface $client,
        int $isolation = TransactionInterface::ISOLATION_COMMITTED
    ): PromiseInterface {
        if($this->goingAway) {
            return Promise\reject((new Exception('Connection is going away')));
        }
        
        if($this->transaction) {
            throw new Exception('Driver is already in transaction');
        }
        
        $this->transaction = true;
        
        switch($isolation) {
            case TransactionInterface::ISOLATION_NO_CHANGE:
                return $this->query($client, 'START TRANSACTION')->then(
                    function () use (&$client, $isolation) {
                        return (new Transaction($client, $this, $isolation));
                    }
                )->then(
                    null,
                    function (\Throwable $e) {
                        $this->transaction = false;
                        throw $e;
                    }
                );
            case TransactionInterface::ISOLATION_UNCOMMITTED:
                $query = 'SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED';
            break;
            case TransactionInterface::ISOLATION_COMMITTED:
                $query = 'SET TRANSACTION ISOLATION LEVEL READ COMMITTED';
            break;
            case TransactionInterface::ISOLATION_REPEATABLE:
                $query = 'SET TRANSACTION ISOLATION LEVEL REPEATABLE READ';
            break;
            case TransactionInterface::ISOLATION_SERIALIZABLE:
                $query = 'SET TRANSACTION ISOLATION LEVEL SERIALIZABLE';
            break;
            default:
                throw new Exception('Invalid isolation level given');
        }
        
        return $this->query($client, $query)->then(
            function () use (&$client, $isolation) {
                return $this->query($client, 'START TRANSACTION')->then(
                    function () use (&$client, $isolation) {
                        return (new Transaction($client, $this, $isolation));
                    }
                );
            }
        )->then(
            null,
            function (\Throwable $e) {
                $this->transaction = false;
                throw $e;
            }
        );
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
     * @param ClientInterface   $client
     * @param CommandInterface  $command
     * @return PromiseInterface
     */
    function runCommand(ClientInterface $client, CommandInterface $command) {
        if($this->goingAway) {
            return Promise\reject((new Exception('Connection is going away')));
        }
        
        return (new Promise\Promise(
            function (callable $resolve, callable $reject) use (&$client, &$command) {
                $command->once(
                    'end',
                    function ($value = null) use (&$client, &$resolve) {
                        if(!$this->transaction) {
                            $client->checkinConnection($this);
                        }
                        
                        $resolve($value);
                    }
                );
                
                $command->once(
                    'error',
                    function (\Throwable $error) use (&$client, &$reject) {
                        if(!$this->transaction) {
                            $client->checkinConnection($this);
                        }
                        
                        $reject($error);
                    }
                );
                
                $this->executeCommand($command);
            }
        ));
    }
    
    /**
     * Runs the given SQL querybuilder.
     * The driver CAN throw an exception if the given querybuilder is not supported.
     * An example would be a SQL querybuilder and a Cassandra driver.
     * @param ClientInterface        $client
     * @param QueryBuilderInterface  $query
     * @return PromiseInterface
     * @throws Exception
     */
    function runQuery(ClientInterface $client, QueryBuilderInterface $query): PromiseInterface {
        if($this->goingAway) {
            return Promise\reject((new Exception('Connection is going away')));
        }
        
        if(!($query instanceof SQLQueryBuilderInterface)) {
            throw new Exception('Given querybuilder must be a SQL querybuilder');
        }
        
        $sql = $query->getQuery();
        $params = $query->getParameters();
        
        return $this->execute($client, $sql, $params);
    }
    
    /**
     * Creates a new cursor to seek through SELECT query results. Resolves with a `CursorInterface` instance.
     * @param ClientInterface  $client
     * @param string           $query
     * @param array            $params
     * @return PromiseInterface
     * @throws \LogicException  Thrown if the driver or DBMS does not support cursors.
     * @throws Exception
     */
    function createReadCursor(ClientInterface $client, string $query, array $params = array()): PromiseInterface {
        if($this->goingAway) {
            return Promise\reject((new Exception('Connection is going away')));
        } elseif(!$this->supportsCursors()) {
            throw new \LogicException('Used DBMS version does not support cursors');
        }
        
        return $this->prepare($client, $query)->then(
            function (Statement $statement) use ($params) {
                if(!$this->supportsCursors()) {
                    $statement->close()->then(
                        null,
                        function () {
                            $this->close();
                        }
                    );
                    
                    throw new \LogicException('Used DBMS version does not support cursors');
                }
                
                return $statement->createReadCursor($params);
            }
        );
    }
    
    /**
     * Executes a command.
     * @param CommandInterface  $command
     * @return void
     * @internal
     */
    function executeCommand(CommandInterface $command): void {
        $this->queue[] = $command;
        
        if($this->parser && $this->busy === static::STATE_IDLE) {
            $this->parser->invokeCommand($this->getNextCommand());
        }
    }
    
    /**
     * Get the handshake message, or null if none received yet.
     * @return HandshakeMessage|null
     */
    function getHandshake(): ?HandshakeMessage {
        if($this->parser) {
            return $this->parser->getHandshakeMessage();
        }
        
        return null;
    }
    
    /**
     * Get the next command, or null.
     * @return CommandInterface|null
     * @internal
     */
    function getNextCommand(): ?CommandInterface {
        if(\count($this->queue) === 0) {
            if($this->goingAway) {
                $this->goingAway->resolve();
            }
            
            return null;
        } elseif($this->busy === static::STATE_BUSY) {
            return null;
        }
        
        /** @var CommandInterface $command */
        $command = \array_shift($this->queue);
        
        if($command->waitForCompletion()) {
            $this->busy = static::STATE_BUSY;
            
            $command->once('error', function () {
                $this->busy = static::STATE_IDLE;
                
                $this->endCommand();
            });
            
            $command->once('end', function () {
                $this->busy = static::STATE_IDLE;
                
                $this->endCommand();
            });
        } else {
            $this->endCommand();
        }
        
        return $command;
    }
    
    /**
     * Get the driver options.
     * @return array
     * @internal
     */
    function getOptions(): array {
        return $this->options;
    }
    
    /**
     * Whether the DBMS supports cursors.
     * @return bool
     * @internal
     */
    function supportsCursors(): bool {
        if($this->getHandshake() === null) {
            return true; // Let's be optimistic
        } elseif($this->cursorSupported !== null) {
            return $this->cursorSupported;
        }
        
        $version = $this->getHandshake()->serverVersion;
        $mariaDB = (int) (\stripos($version, 'MariaDB') !== false);
        $version = \explode('-', $version)[$mariaDB];
        
        $this->cursorSupported = (
            (($this->getHandshake()->capability & CapabilityFlags::CLIENT_PS_MULTI_RESULTS) > 0) &&
            (
                ($mariaDB && \version_compare($version, '10.3', '>=')) ||
                (!$mariaDB && \version_compare($version, '5.7', '>='))
            )
        );
        
        return $this->cursorSupported;
    }
    
    /**
     * Finishes up a command.
     * @return void
     */
    protected function endCommand(): void {
        $this->loop->futureTick(
            function () {
                if($this->goingAway && \count($this->queue) === 0) {
                    $this->goingAway->resolve();
                    return;
                }
                
                $this->parser->invokeCommand($this->getNextCommand());
            }
        );
    }
    
    /**
     * Starts the handshake process.
     * @param array     $credentials
     * @param Deferred  $deferred
     * @return void
     */
    protected function startHandshake(array $credentials, Deferred $deferred): void {
        $listener = function (MessageInterface $message) use ($credentials, &$deferred, &$listener) {
            if($message instanceof HandshakeMessage) {
                $this->parser->removeListener('message', $listener);
                
                $this->connectionState = static::CONNECTION_SETENV;
                $clientFlags = ProtocolParser::CLIENT_CAPABILITIES;
                
                $db = '';
                \extract($credentials);
                
                if($db !== '') {
                    $clientFlags |= CapabilityFlags::CLIENT_CONNECT_WITH_DB;
                }
                
                if($this->charset === null) {
                    $this->charset = CharacterSetFlags::CHARSET_MAP[$message->characterSet] ?? 'latin1';
                }
                
                if(
                    ($message->capability & CapabilityFlags::CLIENT_COMPRESS) !== 0 &&
                    $this->options['compression.enable'] &&
                    \extension_loaded('zlib')
                ) {
                    $this->parser->enableCompression();
                    $clientFlags |= CapabilityFlags::CLIENT_COMPRESS;
                }
                
                // Check if we support auth plugins
                $plugins = DriverFactory::getAuthPlugins();
                $plugin = null;
                
                foreach($plugins as $key => $plug) {
                    if(\is_int($key) && ($message->capability & $key) !== 0) {
                        $plugin = $plug;
                        $clientFlags |= CapabilityFlags::CLIENT_PLUGIN_AUTH;
                        break;
                    } elseif($key === $message->authPluginName) {
                        $plugin = $plug;
                        $clientFlags |= CapabilityFlags::CLIENT_PLUGIN_AUTH;
                        break;
                    }
                }
                
                $remote = \parse_url($this->connection->getRemoteAddress());
                $isNotLocal = $remote['host'] !== '127.0.0.1' && $remote['host'] !== '[::1]';
                
                if($remote !== false && ($isNotLocal || $this->options['tls.forceLocal'])) {
                    if(($message->capability & CapabilityFlags::CLIENT_SSL) !== 0) { // If SSL supported, connect through SSL
                        $clientFlags |= CapabilityFlags::CLIENT_SSL;
                        
                        $ssl = new SSLRequestCommand($message, $clientFlags);
                        
                        $ssl->once('end', function () use ($credentials, $clientFlags, $plugin, &$deferred, &$message) {
                            $this->connectionState = static::CONNECTION_SSL_STARTUP;
                            
                            $this->enableTLS()->then(
                                function () use ($credentials, $clientFlags, $plugin, &$deferred, &$message) {
                                    $this->createHandshakeResponse($message, $credentials, $clientFlags, $plugin, $deferred);
                                },
                                function (\Throwable $error) use (&$deferred) {
                                    $deferred->reject($error);
                                    $this->connection->close();
                                }
                            );
                        });
                        
                        $this->parser->invokeCommand($ssl);
                        return;
                    } elseif($this->options['tls.force'] || $this->options['tls.forceLocal']) {
                        $deferred->reject((new Exception('TLS is not supported by the server')));
                        $this->connection->close();
                        return;
                    }
                }
                
                $this->createHandshakeResponse($message, $credentials, $clientFlags, $plugin, $deferred);
            }
        };
        
        $this->parser->on('message', $listener);
        
        $this->parser->on(
            'message',
            function (MessageInterface $message) {
                if($message instanceof OkResponseMessage) {
                    $this->connectionState = static::CONNECTION_OK;
                }
                
                $this->emit('eventRelay', array('message', $message));
            }
        );
    }
    
    /**
     * Enables TLS on the connection.
     * @return PromiseInterface
     */
    protected function enableTLS(): PromiseInterface {
        // Set required SSL/TLS context options
        foreach($this->options['tls.context'] as $name => $value) {
            \stream_context_set_option($this->connection->stream, 'ssl', $name, $value);
        }
        
        return $this->encryption->enable($this->connection)->then(
            null,
            function (\Throwable $error) {
                $this->connection->close();
                throw new \RuntimeException('Connection failed during TLS handshake: '.$error->getMessage(), $error->getCode());
            }
        );
    }
    
    /**
     * Sends the auth command.
     * @param HandshakeMessage  $message
     * @param array             $credentials
     * @param int               $clientFlags
     * @param string|null       $plugin
     * @param Deferred          $deferred
     * @return void
     */
    protected function createHandshakeResponse(
        HandshakeMessage $message,
        array $credentials,
        int $clientFlags,
        ?string $plugin,
        Deferred $deferred
    ): void {
        $user = '';
        $password = '';
        $db = '';
        
        \extract($credentials);
        
        $auth = new HandshakeResponseCommand($this->parser, $message, $clientFlags, $plugin, $user, $password, $db);
        
        $auth->once(
            'end',
            function () use (&$deferred) {
                $this->loop->futureTick(
                    function () use (&$deferred) {
                        $deferred->resolve();
                    }
                );
            }
        );
        
        $auth->once(
            'error',
            function (\Throwable $error) use (&$deferred) {
                $deferred->reject($error);
                $this->connection->close();
            }
        );
        
        if($plugin) {
            $listener = function (MessageInterface $message) use ($password, &$deferred, &$listener) {
                /** @var AuthPluginInterface|null $plugin */
                static $plugin;
                
                if($message instanceof AuthSwitchRequestMessage) {
                    $name = $message->authPluginName;
                    
                    if($name !== null) {
                        $plugins = DriverFactory::getAuthPlugins();
                        foreach($plugins as $key => $plug) {
                            if($key === $name) {
                                $plugin = new $plug($this->parser, $this->parser->getHandshakeMessage());
                                
                                $command = new AuthSwitchResponseCommand($message, $plugin, $password);
                                $this->parser->invokeCommand($command);
                                return;
                            }
                        }
                    }
                    
                    $deferred->reject((new Exception('Requested authentication method '.($name ? '"'.$name.'" ' : '').'is not supported')));
                } elseif($message instanceof AuthMoreDataMessage) {
                    if($plugin === null) {
                        $deferred->reject((new Exception('No auth plugin is in use, but we received auth more data packet')));
                        $this->connection->close();
                        return;
                    }
                    
                    try {
                        $command = $plugin->receiveMoreData($message);
                        $this->parser->invokeCommand($command);
                        return;
                    } catch (Exception $e) {
                        $deferred->reject($e);
                        $this->connection->close();
                    }
                } elseif($message instanceof OkResponseMessage) {
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
    protected function validateOptions(array $options): void {
        Validator::make(
            array(
                'characters.set' => 'string',
                'characters.collate' => 'string',
                'compression.enable' => 'boolean',
                'connector' => 'class:'.ConnectorInterface::class.'=object',
                'packet.maxAllowedSize' => 'integer|min:0|max:'.ProtocolParser::CLIENT_MAX_PACKET_SIZE,
                'tls.context' => 'array',
                'tls.force' => 'boolean',
                'tls.forceLocal' => 'boolean'
            ),
            true
        )->validate($options, \InvalidArgumentException::class);
    }
}
