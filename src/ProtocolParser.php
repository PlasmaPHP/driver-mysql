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
 * The MySQL Protocol Parser.
 * @internal
 */
class ProtocolParser implements \Evenement\EventEmitterInterface {
    use \Evenement\EventEmitterTrait;
    
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
        \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_FOUND_ROWS |
        \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_LONG_PASSWORD |
        \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_LONG_FLAG |
        \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_LOCAL_FILES |
        \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_INTERACTIVE |
        \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_TRANSACTIONS |
        \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_SECURE_CONNECTION |
        \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_PROTOCOL_41 |
        \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_DEPRECATE_EOF |
        \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_PS_MULTI_RESULTS
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
     * @var \Plasma\Drivers\MySQL\Driver
     */
    protected $driver;
    
    /**
     * @var \React\Socket\ConnectionInterface
     */
    protected $connection;
    
    /**
     * @var int
     */
    protected $state = ProtocolParser::STATE_INIT;
    
    /**
     * @var \Plasma\BinaryBuffer
     */
    protected $buffer;
    
    /**
     * @var \Plasma\BinaryBuffer
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
     * @var \Plasma\BinaryBuffer
     */
    protected $compressionBuffer;
    
    /**
     * @var \Plasma\Drivers\MySQL\Messages\HandshakeMessage|null
     */
    protected $handshakeMessage;
    
    /**
     * @var \Plasma\Drivers\MySQL\Messages\OkResponseMessage|null
     */
    protected $lastOkMessage;
    
    /**
     * @var \Plasma\CommandInterface|null
     */
    protected $currentCommand;
    
    /**
     * @var callable|null
     */
    protected $parseCallback;
    
    /**
     * Constructor.
     * @param \Plasma\Drivers\MySQL\Driver       $driver
     * @param \React\Socket\ConnectionInterface  $connection
     */
    function __construct(\Plasma\Drivers\MySQL\Driver $driver, \React\Socket\ConnectionInterface $connection) {
        $this->driver = $driver;
        $this->connection = $connection;
        
        $this->buffer = new \Plasma\BinaryBuffer();
        $this->messageBuffer = new \Plasma\BinaryBuffer();
        $this->compressionBuffer = new \Plasma\BinaryBuffer();
        
        $this->addEvents();
    }
    
    /**
     * Invoke a command to execute.
     * @param \Plasma\CommandInterface|null  $command
     * @return void
     */
    function invokeCommand(?\Plasma\CommandInterface $command): void {
        if($command === null) {
            return;
        }
        
        $this->currentCommand = $command;
        $this->processCommand();
    }
    
    /**
     * Executes a command, without handling any aftermath.
     * The `onComplete` callback will be immediately invoked, regardless of the `waitForCompletion` value.
     * @param \Plasma\CommandInterface  $command
     * @return void
     */
    function executeCommand(\Plasma\CommandInterface $command): void {
        $this->processCommand($command);
    }
    
    /**
     * Marks the command itself as finished, if currently running.
     * @param \Plasma\Drivers\MySQL\Commands\CommandInterface  $command
     * @return void
     */
    function markCommandAsFinished(\Plasma\CommandInterface $command): void {
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
     * @return \Plasma\Drivers\MySQL\Messages\HandshakeMessage|null
     */
    function getHandshakeMessage(): ?\Plasma\Drivers\MySQL\Messages\HandshakeMessage {
        return $this->handshakeMessage;
    }
    
    /**
     * Get the last ok response message, or null.
     * @return \Plasma\Drivers\MySQL\Messages\OkResponseMessage|null
     */
    function getLastOkMessage(): ?\Plasma\Drivers\MySQL\Messages\OkResponseMessage {
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
            
            $length = \Plasma\BinaryBuffer::writeInt3($partlen);
            $sequence = \Plasma\BinaryBuffer::writeInt1((++$this->sequenceID));
            
            $packet = $length.$sequence.$partial;
            
            if($this->compressionEnabled && $this->state === static::STATE_OK) {
                $packet = $this->compressPacket($packet);
            }
            
            $this->connection->write($packet);
        } while($packlen > static::CLIENT_MAX_PACKET_SIZE);
        
        // If the packet is exactly the max size, we have to send two packets
        if($initPacklen === static::CLIENT_MAX_PACKET_SIZE) {
            $length = \Plasma\BinaryBuffer::writeInt3(0);
            $sequence = \Plasma\BinaryBuffer::writeInt1((++$this->sequenceID));
            $packet = $length.$sequence;
            
            if($this->compressionEnabled && $this->state === static::STATE_OK) {
                $packet = $this->compressPacket($packet);
            }
            
            $this->connection->write($packet);
        }
    }
    
    /**
     * Sets the parse callback.
     * @param callable $callback
     * @return void
     */
    function setParseCallback(callable $callback): void {
        $this->parseCallback = $callback;
    }
    
    /**
     * Processes a command.
     * @param \Plasma\CommandInterface|null  $command
     * @return void
     */
    protected function processCommand(?\Plasma\CommandInterface $command = null) {
        if($command === null && $this->currentCommand instanceof \Plasma\CommandInterface) {
            $command = $this->currentCommand;
            
            if($this->currentCommand instanceof \Plasma\Drivers\MySQL\Commands\CommandInterface) {
                $state = $command->setParserState();
                if($state !== -1) {
                    $this->state = $state;
                }
            }
        }
        
        if($command === null) {
            return;
        }
        
        if(!($command instanceof \Plasma\Drivers\MySQL\Commands\CommandInterface) || $command->resetSequence()) {
            $this->sequenceID = -1;
            $this->compressionID = -1;
        }
        
        try {
            $msg = $command->getEncodedMessage();
        } catch (\Plasma\Exception $e) {
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
     */
    protected function processBuffer() {
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
            $this->messageBuffer = new \Plasma\BinaryBuffer();
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
        
        /** @var \Plasma\Drivers\MySQL\Messages\MessageInterface  $message */
        $message = null;
        
        if($this->state === static::STATE_INIT) {
            $message = new \Plasma\Drivers\MySQL\Messages\HandshakeMessage($this);
        } else {
            $firstChar = $buffer->read(1);
            
            $okRespID = \Plasma\Drivers\MySQL\Messages\OkResponseMessage::getID();
            $isOkMessage = (
                (
                    $firstChar === $okRespID &&
                    (!($this->currentCommand instanceof \Plasma\Drivers\MySQL\Commands\QueryCommand)
                        || \strtoupper(\substr($this->currentCommand->getQuery(), 0, 6)) !== 'SELECT') // Fix for MySQL 5.7
                ) ||
                (
                    $firstChar === \Plasma\Drivers\MySQL\Messages\EOFMessage::getID() &&
                    ($this->handshakeMessage->capability & \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_DEPRECATE_EOF) !== 0
                )
            );
            
            switch(true) {
                case ($firstChar === \Plasma\Drivers\MySQL\Messages\ErrResponseMessage::getID()):
                    $message = new \Plasma\Drivers\MySQL\Messages\ErrResponseMessage($this);
                break;
                case ($this->currentCommand instanceof \Plasma\Drivers\MySQL\Commands\StatementPrepareCommand && $firstChar === $okRespID):
                    $message = new \Plasma\Drivers\MySQL\Messages\PrepareStatementOkMessage($this);
                break;
                case $isOkMessage:
                    $message = new \Plasma\Drivers\MySQL\Messages\OkResponseMessage($this);
                    $this->lastOkMessage = $message;
                    
                    $this->driver->getLoop()->futureTick(function () use ($message) {
                        $this->driver->emit('eventRelay', array('serverOkMessage', $message));
                    });
                break;
                case ($this->state < static::STATE_OK && $firstChar === \Plasma\Drivers\MySQL\Messages\AuthMoreDataMessage::getID()):
                    $message = new \Plasma\Drivers\MySQL\Messages\AuthMoreDataMessage($this);
                break;
                case ($this->state < static::STATE_OK && $firstChar === \Plasma\Drivers\MySQL\Messages\AuthSwitchRequestMessage::getID()):
                    $message = new \Plasma\Drivers\MySQL\Messages\AuthSwitchRequestMessage($this);
                break;
                case ($this->state < static::STATE_OK && $firstChar === \Plasma\Drivers\MySQL\Messages\AuthMoreDataMessage::getID()):
                    $message = new \Plasma\Drivers\MySQL\Messages\AuthMoreDataMessage($this);
                break;
                case ($firstChar === \Plasma\Drivers\MySQL\Messages\EOFMessage::getID() && $length < 6):
                    $message = new \Plasma\Drivers\MySQL\Messages\EOFMessage($this);
                break;
                case ($firstChar === \Plasma\Drivers\MySQL\Messages\LocalInFileRequestMessage::getID()):
                    if($this->driver->getOptions()['localInFile.enable']) {
                        $message = new \Plasma\Drivers\MySQL\Messages\LocalInFileRequestMessage($this);
                    } else {
                        $this->emit('error', array((new \Plasma\Exception('MySQL server requested a local file, but the driver options is disabled'))));
                        
                        if($this->buffer->getSize() > 0) {
                            $this->driver->getLoop()->futureTick(function () {
                                $this->processBuffer();
                            });
                        }
                        
                        return;
                    }
                break;
                default:
                    $buffer->prepend($firstChar);
                    
                    if($this->parseCallback !== null) {
                        $parse = $this->parseCallback;
                        $this->parseCallback = null;
                        
                        $caller = new \Plasma\Drivers\MySQL\ProtocolOnNextCaller($this, $buffer);
                        $parse($caller);
                    } elseif($this->currentCommand !== null) {
                        $command = $this->currentCommand;
                        
                        $caller = new \Plasma\Drivers\MySQL\ProtocolOnNextCaller($this, $buffer);
                        $command->onNext($caller);
                        
                        if($command->hasFinished()) {
                            $this->currentCommand = null;
                            $command->onComplete();
                        }
                    }
                    
                    if($this->buffer->getSize() > 0) {
                        $this->driver->getLoop()->futureTick(function () {
                            $this->processBuffer();
                        });
                    }
                    
                    return;
                break;
            }
        }
        
        $state = $message->setParserState();
        if($state !== -1) {
            $this->state = $state;
        }
        
        if($message instanceof \Plasma\Drivers\MySQL\Messages\HandshakeMessage) {
            $this->handshakeMessage = $message;
        }
        
        $this->handleMessage($buffer, $message);
    }
    
    /**
     * Handles an incoming message.
     * @param \Plasma\BinaryBuffer                             $buffer
     * @param \Plasma\Drivers\MySQL\Messages\MessageInterface  $message
     * @return void
     */
    function handleMessage(\Plasma\BinaryBuffer $buffer, \Plasma\Drivers\MySQL\Messages\MessageInterface $message) {
        try {
            $buffer = $message->parseMessage($buffer);
            if(!$buffer) {
                return;
            }
            
            if($this->currentCommand !== null) {
                if(
                    ($message instanceof \Plasma\Drivers\MySQL\Messages\OkResponseMessage || $message instanceof \Plasma\Drivers\MySQL\Messages\EOFMessage)
                    && $this->currentCommand->hasFinished()
                ) {
                    $command = $this->currentCommand;
                    $this->currentCommand = null;
                    
                    $command->onComplete();
                } elseif($message instanceof \Plasma\Drivers\MySQL\Messages\ErrResponseMessage) {
                    $error = new \Plasma\Exception($message->errorMessage, $message->errorCode);
                    
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
            } elseif($message instanceof \Plasma\Drivers\MySQL\Messages\ErrResponseMessage) {
                $error = new \Plasma\Exception($message->errorMessage, $message->errorCode);
                $this->emit('error', array($error));
            }
            
            $this->emit('message', array($message));
        } catch (\Plasma\Drivers\MySQL\Messages\ParseException $e) {
            $state = $e->getState();
            if($state !== null) {
                $this->state = $state;
            }
            
            $buffer = $e->getBuffer();
            if($buffer !== null) {
                $this->buffer->clear();
                $this->buffer->append($buffer);
            }
            
            if($this->currentCommand !== null) {
                $this->currentCommand->onError($e);
            }
            
            $this->emit('error', array($e));
            $this->connection->close();
        } catch (\Plasma\Exception $e) {
            if($this->currentCommand !== null) {
                $command = $this->currentCommand;
                $this->currentCommand = null;
                
                $command->onError($e);
            } else {
                $this->emit('error', array($e));
            }
        }
        
        if($this->buffer->getSize() > 0) {
            $this->driver->getLoop()->futureTick(function () {
                $this->processBuffer();
            });
        }
    }
    
    /**
     * Compresses a packet.
     * @param string  $packet
     * @return string
     */
    protected function compressPacket(string $packet): string {
        $length = \strlen($packet);
        $packetlen = \Plasma\BinaryBuffer::writeInt3($length);
        $id = \Plasma\BinaryBuffer::writeInt1((++$this->compressionID));
        
        if($length < $this->compressionSizeThreshold) {
            return $packetlen.$id.\Plasma\BinaryBuffer::writeInt3(0).$packet;
        }
        
        $compressed = \zlib_encode($packet, \ZLIB_ENCODING_DEFLATE);
        $compresslen = \Plasma\BinaryBuffer::writeInt3(\strlen($compressed));
        
        return $compresslen.$id.$packetlen.$compressed;
    }
    
    /**
     * Decompresses the buffer.
     * @return void
     */
    protected function decompressBuffer(): void {
        $buffer = new \Plasma\BinaryBuffer();
        
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
    protected function addEvents() {
        $this->connection->on('data', function ($chunk) {
            if($this->compressionEnabled && $this->state === static::STATE_OK) {
                $this->compressionBuffer->append($chunk);
                
                if($this->compressionBuffer->getSize() > 7) {
                    $this->decompressBuffer();
                }
            } else {
                $this->buffer->append($chunk);
            }
            
            $this->processBuffer();
        });
        
        $this->connection->on('close', function () {
            $this->handleClose();
        });
    }
    
    /**
     * Connection close handler.
     * @return void
     */
    protected function handleClose() {
        if($this->state === static::STATE_AUTH || $this->state === static::STATE_AUTH_SENT) {
            $this->state = static::STATE_AUTH_ERROR;
        }
        
        $this->buffer->clear();
        $this->messageBuffer->clear();
        $this->compressionBuffer->clear();
    }
}
