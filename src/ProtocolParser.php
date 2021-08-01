<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
 */

namespace Plasma\Drivers\MySQL;

use Evenement\EventEmitterInterface;
use Evenement\EventEmitterTrait;
use Plasma\BinaryBuffer;
use Plasma\CommandInterface as BaseCommandInterface;
use Plasma\Drivers\MySQL\Commands\CommandInterface;
use Plasma\Drivers\MySQL\Commands\QueryCommand;
use Plasma\Drivers\MySQL\Commands\StatementPrepareCommand;
use Plasma\Drivers\MySQL\Messages\AuthMoreDataMessage;
use Plasma\Drivers\MySQL\Messages\AuthSwitchRequestMessage;
use Plasma\Drivers\MySQL\Messages\EOFMessage;
use Plasma\Drivers\MySQL\Messages\ErrResponseMessage;
use Plasma\Drivers\MySQL\Messages\HandshakeMessage;
use Plasma\Drivers\MySQL\Messages\LocalInFileRequestMessage;
use Plasma\Drivers\MySQL\Messages\MessageInterface;
use Plasma\Drivers\MySQL\Messages\OkResponseMessage;
use Plasma\Drivers\MySQL\Messages\ParseException;
use Plasma\Drivers\MySQL\Messages\PrepareStatementOkMessage;
use Plasma\Exception;
use React\Socket\ConnectionInterface;

/**
 * The MySQL Protocol Parser.
 * @internal
 */
class ProtocolParser implements EventEmitterInterface {
    use EventEmitterTrait;
    
    /**
     * @var int
     */
    const STATE_INIT = 0;
    
    /**
     * @var int
     */
    const STATE_HANDSHAKE = 1;
    
    /**
     * @var int
     */
    const STATE_HANDSHAKE_ERROR = 2;
    
    /**
     * @var int
     */
    const STATE_AUTH = 5;
    
    /**
     * @var int
     */
    const STATE_AUTH_SENT = 6;
    
    /**
     * @var int
     */
    const STATE_AUTH_ERROR = 7;
    
    /**
     * @var int
     */
    const STATE_OK = 9;
    
    /**
     * @var int
     */
    const CLIENT_CAPABILITIES = (
        CapabilityFlags::CLIENT_FOUND_ROWS |
        CapabilityFlags::CLIENT_LONG_PASSWORD |
        CapabilityFlags::CLIENT_LONG_FLAG |
        CapabilityFlags::CLIENT_LOCAL_FILES |
        CapabilityFlags::CLIENT_INTERACTIVE |
        CapabilityFlags::CLIENT_TRANSACTIONS |
        CapabilityFlags::CLIENT_SECURE_CONNECTION |
        CapabilityFlags::CLIENT_PROTOCOL_41 |
        CapabilityFlags::CLIENT_DEPRECATE_EOF |
        CapabilityFlags::CLIENT_PS_MULTI_RESULTS
    );
    
    /**
     * @var int
     */
    const CLIENT_MAX_PACKET_SIZE = 0xFFFFFF;
    
    /**
     * @var int
     */
    const CLIENT_CHARSET_NUMBER = 0x21;
    
    /**
     * @var Driver
     */
    protected $driver;
    
    /**
     * @var ConnectionInterface
     */
    protected $connection;
    
    /**
     * @var int
     */
    protected $state = ProtocolParser::STATE_INIT;
    
    /**
     * @var BinaryBuffer
     */
    protected $buffer;
    
    /**
     * @var BinaryBuffer
     */
    protected $messageBuffer;
    
    /**
     * The sequence ID is incremented with each packet and may wrap around.
     * It starts at 0 and is reset to 0 when a new command begins in the Command Phase.
     * @var int
     * @see https://dev.mysql.com/doc/internals/en/sequence-id.html
     */
    protected $sequenceID = -1;
    
    /**
     * Whether we use compression.
     * @var bool
     */
    protected $compressionEnabled = false;
    
    /**
     * The compression ID is incremented with each packet and may wrap around.
     * The compression ID is independent to the sequence ID.
     * @var int
     * @see https://dev.mysql.com/doc/internals/en/compressed-packet-header.html
     */
    protected $compressionID = -1;
    
    /**
     * Small packets should not be compressed. This defines a minimum size for compression.
     * @var int
     * @see https://dev.mysql.com/doc/internals/en/uncompressed-payload.html
     */
    protected $compressionSizeThreshold = 50;
    
    /**
     * @var BinaryBuffer
     */
    protected $compressionBuffer;
    
    /**
     * @var HandshakeMessage|null
     */
    protected $handshakeMessage;
    
    /**
     * @var OkResponseMessage|null
     */
    protected $lastOkMessage;
    
    /**
     * @var BaseCommandInterface|null
     */
    protected $currentCommand;
    
    /**
     * @var callable|null
     */
    protected $parseCallback;
    
    /**
     * Constructor.
     * @param Driver               $driver
     * @param ConnectionInterface  $connection
     */
    function __construct(Driver $driver, ConnectionInterface $connection) {
        $this->driver = $driver;
        $this->connection = $connection;
        
        $this->buffer = new BinaryBuffer();
        $this->messageBuffer = new BinaryBuffer();
        $this->compressionBuffer = new BinaryBuffer();
        
        $this->addEvents();
    }
    
    /**
     * Invoke a command to execute.
     * @param BaseCommandInterface|null  $command
     * @return void
     */
    function invokeCommand(?BaseCommandInterface $command): void {
        if($command === null) {
            return;
        }
        
        $this->currentCommand = $command;
        $this->processCommand();
    }
    
    /**
     * Executes a command, without handling any aftermath.
     * The `onComplete` callback will be immediately invoked, regardless of the `waitForCompletion` value.
     * @param BaseCommandInterface  $command
     * @return void
     */
    function executeCommand(BaseCommandInterface $command): void {
        $this->processCommand($command);
    }
    
    /**
     * Marks the command itself as finished, if currently running.
     * @param BaseCommandInterface  $command
     * @return void
     */
    function markCommandAsFinished(BaseCommandInterface $command): void {
        if($command === $this->currentCommand) {
            $this->currentCommand = null;
        }
        
        $command->onComplete();
    }
    
    /**
     * Get the parser state.
     * @return int
     */
    function getState(): int {
        return $this->state;
    }
    
    /**
     * Get the handshake message, or null.
     * @return HandshakeMessage|null
     */
    function getHandshakeMessage(): ?HandshakeMessage {
        return $this->handshakeMessage;
    }
    
    /**
     * Get the last ok response message, or null.
     * @return OkResponseMessage|null
     */
    function getLastOkMessage(): ?OkResponseMessage {
        return $this->lastOkMessage;
    }
    
    /**
     * Enables compression.
     * @return void
     */
    function enableCompression(): void {
        $this->compressionEnabled = true;
    }
    
    /**
     * Sends a packet to the server.
     * @param string  $packet
     * @return void
     */
    function sendPacket(string $packet): void {
        $initPacklen = \strlen($packet);
        
        do {
            $partial = \substr($packet, 0, static::CLIENT_MAX_PACKET_SIZE);
            $partlen = \strlen($partial);
            
            $packet = \substr($packet, static::CLIENT_MAX_PACKET_SIZE);
            $packlen = \strlen($packet);
            
            $length = BinaryBuffer::writeInt3($partlen);
            $sequence = BinaryBuffer::writeInt1((++$this->sequenceID));
            
            $packet = $length.$sequence.$partial;
            
            if($this->compressionEnabled && $this->state === static::STATE_OK) {
                $packet = $this->compressPacket($packet);
            }
            
            $this->connection->write($packet);
        } while($packlen > static::CLIENT_MAX_PACKET_SIZE);
        
        // If the packet is exactly the max size, we have to send two packets
        if($initPacklen === static::CLIENT_MAX_PACKET_SIZE) {
            $length = BinaryBuffer::writeInt3(0);
            $sequence = BinaryBuffer::writeInt1((++$this->sequenceID));
            $packet = $length.$sequence;
            
            if($this->compressionEnabled && $this->state === static::STATE_OK) {
                $packet = $this->compressPacket($packet);
            }
            
            $this->connection->write($packet);
        }
    }
    
    /**
     * Sets the parse callback.
     * @param callable  $callback
     * @return void
     */
    function setParseCallback(callable $callback): void {
        $this->parseCallback = $callback;
    }
    
    /**
     * Processes a command.
     * @param BaseCommandInterface|null  $command
     * @return void
     */
    protected function processCommand(?BaseCommandInterface $command = null): void {
        if($command === null && $this->currentCommand instanceof BaseCommandInterface) {
            $command = $this->currentCommand;
            
            if($this->currentCommand instanceof CommandInterface) {
                $state = $command->setParserState();
                if($state !== -1) {
                    $this->state = $state;
                }
            }
        }
        
        if($command === null) {
            return;
        }
        
        if(!($command instanceof CommandInterface) || $command->resetSequence()) {
            $this->sequenceID = -1;
            $this->compressionID = -1;
        }
        
        try {
            $msg = $command->getEncodedMessage();
        } /** @noinspection PhpRedundantCatchClauseInspection */
        catch (Exception $e) {
            $this->currentCommand = null;
            $command->onError($e);
            return;
        }
        
        $this->sendPacket($msg);
        
        if($command !== $this->currentCommand || !$command->waitForCompletion()) {
            $command->onComplete();
            
            if($command === $this->currentCommand) {
                $this->currentCommand = null;
            }
        }
    }
    
    /**
     * Processes the buffer.
     * @return void
     * @throws Exception
     * @throws ParseException
     */
    protected function processBuffer(): void {
        if($this->buffer->getSize() < 4) {
            return;
        }
        
        $buffer = clone $this->buffer;
        
        $length = $buffer->readInt3();
        $this->sequenceID = $buffer->readInt1();
        
        if($length === static::CLIENT_MAX_PACKET_SIZE) {
            $this->buffer->read(($length + 4));
            $this->messageBuffer->append($buffer->read($length));
            return;
        } elseif($this->messageBuffer->getSize() > 0) {
            $this->messageBuffer->append($buffer->read($length));
            $buffer = $this->messageBuffer;
            $this->messageBuffer = new BinaryBuffer();
        }
        
        if($buffer->getSize() < $length) {
            return;
        }
        
        if($length > 0) {
            $this->buffer->read(($length + 4));
            $buffer->slice(0, $length);
        } else {
            $this->buffer->slice($buffer->getSize());
        }
        
        if($buffer->getSize() === 0) {
            return;
        }
        
        /** @var MessageInterface $message */
        $message = null;
        
        if($this->state === static::STATE_INIT) {
            $message = new HandshakeMessage($this);
        } else {
            $firstChar = $buffer->read(1);
            
            $okRespID = OkResponseMessage::getID();
            $isOkMessage = (
                (
                    $firstChar === $okRespID &&
                    (!($this->currentCommand instanceof QueryCommand)
                        || \stripos($this->currentCommand->getQuery(), 'SELECT') !== 0) // Fix for MySQL 5.7
                ) ||
                (
                    $firstChar === EOFMessage::getID() &&
                    ($this->handshakeMessage->capability & CapabilityFlags::CLIENT_DEPRECATE_EOF) !== 0
                )
            );
            
            switch(true) {
                case ($firstChar === ErrResponseMessage::getID()):
                    $message = new ErrResponseMessage($this);
                break;
                case ($this->currentCommand instanceof StatementPrepareCommand && $firstChar === $okRespID):
                    $message = new PrepareStatementOkMessage($this);
                break;
                case $isOkMessage:
                    $message = new OkResponseMessage($this);
                    $this->lastOkMessage = $message;
                    
                    $this->driver->getLoop()->futureTick(
                        function () use ($message) {
                            $this->driver->emit('eventRelay', array('serverOkMessage', $message));
                        }
                    );
                break;
                case ($this->state < static::STATE_OK && $firstChar === AuthMoreDataMessage::getID()):
                    $message = new AuthMoreDataMessage($this);
                break;
                case ($this->state < static::STATE_OK && $firstChar === AuthSwitchRequestMessage::getID()):
                    $message = new AuthSwitchRequestMessage($this);
                break;
                case ($firstChar === EOFMessage::getID() && $length < 6):
                    $message = new EOFMessage($this);
                break;
                case ($firstChar === LocalInFileRequestMessage::getID()):
                    if($this->driver->getOptions()['localInFile.enable']) {
                        $message = new LocalInFileRequestMessage($this);
                    } else {
                        $this->emit('error', array((new Exception('MySQL server requested a local file, but the driver options is disabled'))));
                        
                        if($this->buffer->getSize() > 0) {
                            $this->driver->getLoop()->futureTick(
                                function () {
                                    $this->processBuffer();
                                }
                            );
                        }
                        
                        return;
                    }
                break;
                default:
                    $buffer->prepend($firstChar);
                    
                    if($this->parseCallback !== null) {
                        $parse = $this->parseCallback;
                        $this->parseCallback = null;
                        
                        $caller = new ProtocolOnNextCaller($this, $buffer);
                        $parse($caller);
                    } elseif($this->currentCommand !== null) {
                        $command = $this->currentCommand;
                        
                        $caller = new ProtocolOnNextCaller($this, $buffer);
                        $command->onNext($caller);
                        
                        if($command->hasFinished()) {
                            $this->currentCommand = null;
                            $command->onComplete();
                        }
                    }
                    
                    if($this->buffer->getSize() > 0) {
                        $this->driver->getLoop()->futureTick(
                            function () {
                                $this->processBuffer();
                            }
                        );
                    }
                    
                    return;
            }
        }
        
        $state = $message->setParserState();
        if($state !== -1) {
            $this->state = $state;
        }
        
        if($message instanceof HandshakeMessage) {
            $this->handshakeMessage = $message;
        }
        
        $this->handleMessage($buffer, $message);
    }
    
    /**
     * Handles an incoming message.
     * @param BinaryBuffer      $buffer
     * @param MessageInterface  $message
     * @return void
     */
    function handleMessage(BinaryBuffer $buffer, MessageInterface $message): void {
        try {
            $buffer2 = $message->parseMessage($buffer);
            if(!$buffer2) {
                return;
            }
            
            if($this->currentCommand !== null) {
                if(
                    ($message instanceof OkResponseMessage || $message instanceof EOFMessage)
                    && $this->currentCommand->hasFinished()
                ) {
                    $command = $this->currentCommand;
                    $this->currentCommand = null;
                    
                    $command->onComplete();
                } /** @noinspection NotOptimalIfConditionsInspection */
                elseif($message instanceof ErrResponseMessage) {
                    $error = new Exception($message->errorMessage, $message->errorCode);
                    
                    $command = $this->currentCommand;
                    $this->currentCommand = null;
                    
                    $command->onError($error);
                } else {
                    $command = $this->currentCommand;
                    $command->onNext($message);
                    
                    if($command->hasFinished()) {
                        if($this->currentCommand === $command) {
                            $this->currentCommand = null;
                        }
                        
                        $command->onComplete();
                    }
                }
            } elseif($message instanceof ErrResponseMessage) {
                $error = new Exception($message->errorMessage, $message->errorCode);
                $this->emit('error', array($error));
            }
            
            $this->emit('message', array($message));
        } catch (ParseException $e) {
            $state = $e->getState();
            if($state !== null) {
                $this->state = $state;
            }
            
            $buffer2 = $e->getBuffer();
            if($buffer2 !== null) {
                $this->buffer->clear();
                $this->buffer->append($buffer2);
            }
            
            if($this->currentCommand !== null) {
                $this->currentCommand->onError($e);
            }
            
            $this->emit('error', array($e));
            $this->connection->close();
        } /** @noinspection PhpRedundantCatchClauseInspection */
        catch (Exception $e) {
            if($this->currentCommand !== null) {
                $command = $this->currentCommand;
                $this->currentCommand = null;
                
                $command->onError($e);
            } else {
                $this->emit('error', array($e));
            }
        }
        
        if($this->buffer->getSize() > 0) {
            $this->driver->getLoop()->futureTick(
                function () {
                    $this->processBuffer();
                }
            );
        }
    }
    
    /**
     * Compresses a packet.
     * @param string  $packet
     * @return string
     */
    protected function compressPacket(string $packet): string {
        $length = \strlen($packet);
        $packetlen = BinaryBuffer::writeInt3($length);
        $id = BinaryBuffer::writeInt1((++$this->compressionID));
        
        if($length < $this->compressionSizeThreshold) {
            return $packetlen.$id.BinaryBuffer::writeInt3(0).$packet;
        }
        
        /** @noinspection PhpComposerExtensionStubsInspection */
        $compressed = \zlib_encode($packet, \ZLIB_ENCODING_DEFLATE);
        $compresslen = BinaryBuffer::writeInt3(\strlen($compressed));
        
        return $compresslen.$id.$packetlen.$compressed;
    }
    
    /**
     * Decompresses the buffer.
     * @return void
     */
    protected function decompressBuffer(): void {
        $buffer = new BinaryBuffer();
        
        // Copy packet header to new buffer
        for($i = 0; $i < 7; $i++) {
            $buffer->append($this->compressionBuffer[$i]);
        }
        
        $length = $buffer->readInt3();
        $this->compressionID = $buffer->readInt1();
        $uncompressedLength = $buffer->readInt3();
        
        if(($this->compressionBuffer->getSize() - 7) < $length) {
            return;
        }
        
        $this->compressionBuffer->read(7);
        $buffer = null;
        
        if($uncompressedLength === 0) {
            $this->buffer->append($this->compressionBuffer->read($length));
            return;
        }
        
        $rawPacket = $this->compressionBuffer->read($length);
        
        /** @noinspection PhpComposerExtensionStubsInspection */
        $packet = \zlib_decode($rawPacket, $uncompressedLength);
        
        if(\strlen($packet) !== $uncompressedLength) {
            $packet = "\xFF\x00\x00\x00     Invalid compressed packet";
            $this->connection->end($packet);
            
            return;
        }
        
        $this->buffer->append($packet);
        
        if($this->compressionBuffer->getSize() > 7) {
            $this->decompressBuffer();
        }
    }
    
    /**
     * Adds the events to the connection.
     * @return void
     */
    protected function addEvents(): void {
        $this->connection->on(
            'data',
            function ($chunk) {
                if($this->compressionEnabled && $this->state === static::STATE_OK) {
                    $this->compressionBuffer->append($chunk);
                    
                    if($this->compressionBuffer->getSize() > 7) {
                        $this->decompressBuffer();
                    }
                } else {
                    $this->buffer->append($chunk);
                }
                
                $this->processBuffer();
            }
        );
        
        $this->connection->on(
            'close',
            function () {
                $this->handleClose();
            }
        );
    }
    
    /**
     * Connection close handler.
     * @return void
     */
    protected function handleClose(): void {
        if($this->state === static::STATE_AUTH || $this->state === static::STATE_AUTH_SENT) {
            $this->state = static::STATE_AUTH_ERROR;
        }
        
        $this->buffer->clear();
        $this->messageBuffer->clear();
        $this->compressionBuffer->clear();
    }
}
