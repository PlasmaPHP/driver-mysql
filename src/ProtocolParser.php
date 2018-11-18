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
        \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_LONG_PASSWORD |
        \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_LONG_FLAG |
        \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_LOCAL_FILES |
        \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_INTERACTIVE |
        \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_TRANSACTIONS |
        \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_SECURE_CONNECTION |
        \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_PROTOCOL_41 |
        \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_DEPRECATE_EOF
    );
    
    /**
     * @var int
     */
    const CLIENT_MAX_PACKET_SIZE = 0x1000000;
    
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
    protected $sequenceID = 0;
    
    /**
     * @var \Plasma\Drivers\MySQL\Messages\HandshakeMessage|null
     */
    protected $handshakeMessage;
    
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
     * Sends a packet to the server.
     * @return void
     */
    function sendPacket(string $packet): void {
        $maxSize = static::CLIENT_MAX_PACKET_SIZE - 4;
        
        do {
            $partial = \substr($packet, 0, $maxSize);
            $packet = \substr($packet, $maxSize);
            
            $length = \Plasma\BinaryBuffer::writeInt3(\strlen($partial));
            $sequence = \Plasma\BinaryBuffer::writeInt1((++$this->sequenceID));
            
            $this->connection->write($length.$sequence.$partial);
        } while(\strlen($packet) > $maxSize);
    }
    
    /**
     * Sets the parse callback.
     * @param callable $callback
     * @return void
     */
    function setParseCallback(callable $callback): void {
        $this->parseCallback($callback);
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
        }
        
        \assert((\Plasma\Drivers\MySQL\Messages\MessageUtility::debug('Processing command '.get_class($command)) || true));
        
        $this->sendPacket($command->getEncodedMessage());
        
        if($command !== $this->currentCommand || !$command->waitForCompletion()) {
            \assert((\Plasma\Drivers\MySQL\Messages\MessageUtility::debug('Mark command as completed') || true));
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
        \assert((\Plasma\Drivers\MySQL\Messages\MessageUtility::debug('ProtocolParser::processBuffer called') || true));
        
        if($this->buffer->getSize() < 4) {
            \assert((\Plasma\Drivers\MySQL\Messages\MessageUtility::debug('Not enough data received for packet header ('.$this->buffer->getSize().')') || true));
            return;
        }
        
        $buffer = clone $this->buffer;
        
        $length = $buffer->readInt3();
        $this->sequenceID = $buffer->readInt1();
        
        \assert((\Plasma\Drivers\MySQL\Messages\MessageUtility::debug('First 10 bytes: '.implode(', ', unpack('C*', \substr($this->buffer->getContents(), 0, 10)))) || true));
        \assert((\Plasma\Drivers\MySQL\Messages\MessageUtility::debug('Read packet header length ('.$length.') and sequence ('.$this->sequenceID.')') || true));
        
        if($length === 0xFFFFFF) {
            $this->buffer->read(($length + 4));
            $this->messageBuffer->append($buffer->read($length));
            
            \assert((\Plasma\Drivers\MySQL\Messages\MessageUtility::debug('returned, 16mb packet received, waiting for the last one to arrive') || true));
            return;
        } elseif($this->messageBuffer->getSize() > 0) {
            $this->messageBuffer->append($buffer->read($length));
            $buffer = $this->messageBuffer;
            $this->messageBuffer = new \Plasma\BinaryBuffer();
        }
        
        if($buffer->getSize() < $length) {
            \assert((\Plasma\Drivers\MySQL\Messages\MessageUtility::debug('returned, insufficent length: '.$buffer->getSize().', '.$length.' required') || true));
            return;
        }
        
        if($length > 0) {
            $this->buffer->read(($length + 4));
            $buffer->slice(0, $length);
        } else {
            $this->buffer->slice($buffer->getSize());
        }
        
        if($buffer->getSize() === 0) {
            \assert((\Plasma\Drivers\MySQL\Messages\MessageUtility::debug('Buffer length is 0') || true));
            return;
        }
        
        /** @var \Plasma\Drivers\MySQL\Messages\MessageInterface  $message */
        $message = null;
        
        if($this->state === static::STATE_INIT) {
            $message = new \Plasma\Drivers\MySQL\Messages\HandshakeMessage($this);
        } else {
            $firstChar = $buffer->read(1);
            \assert((\Plasma\Drivers\MySQL\Messages\MessageUtility::debug('Received Message char "'.$firstChar.'" (0x'.\dechex(\ord($firstChar)).') - buffer length: '.$buffer->getSize()) || true));
            
            $okRespID = \Plasma\Drivers\MySQL\Messages\OkResponseMessage::getID();
            $isOkMessage = (
                (
                    $firstChar === $okRespID ||
                    (
                        $firstChar === \Plasma\Drivers\MySQL\Messages\EOFMessage::getID() &&
                        ($this->handshakeMessage->capability & \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_DEPRECATE_EOF) !== 0
                    )
                ) &&
                !($this->currentCommand instanceof \Plasma\Drivers\MySQL\Commands\StatementExecuteCommand)
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
                break;
                case ($firstChar === \Plasma\Drivers\MySQL\Messages\EOFMessage::getID() && $length < 6):
                    $message = new \Plasma\Drivers\MySQL\Messages\EOFMessage($this);
                break;
                default:
                    $buffer->prepend($firstChar);
                    
                    if($this->parseCallback !== null) {
                        $parse = $this->parseCallback;
                        $this->parseCallback = null;
                        
                        $caller = new \Plasma\Drivers\MySQL\ProtocolOnNextCaller($this, $buffer);
                        $parse($caller);
                    } elseif($this->currentCommand !== null) {
                        $caller = new \Plasma\Drivers\MySQL\ProtocolOnNextCaller($this, $buffer);
                        $this->currentCommand->onNext($caller);
                    }
                    
                    \assert((\Plasma\Drivers\MySQL\Messages\MessageUtility::debug('Left over buffer: '.$buffer->getSize()) || true));
                    
                    if($this->buffer->getSize() > 0) {
                        \assert((\Plasma\Drivers\MySQL\Messages\MessageUtility::debug('Scheduling future read with '.$this->buffer->getSize().' bytes') || true));
                        
                        $this->driver->getLoop()->futureTick(function () {
                            $this->processBuffer();
                        });
                    }
                    
                    return;
                break;
            }
        }
        
        \assert((\Plasma\Drivers\MySQL\Messages\MessageUtility::debug('Received Message '.\get_class($message)) || true));
        
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
            $buffer = $message->parseMessage($buffer, $this);
            if(!$buffer) {
                \assert((\Plasma\Drivers\MySQL\Messages\MessageUtility::debug('returned handle (unsufficent buffer length)') || true));
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
                \assert((\Plasma\Drivers\MySQL\Messages\MessageUtility::debug(
                    'Received Error Response Message with message: '.$message->errorMessage
                ) || true));
                
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
        }
        
        if($this->buffer->getSize() > 0) {
            \assert((\Plasma\Drivers\MySQL\Messages\MessageUtility::debug('Scheduling future read (msg) with '.$this->buffer->getSize().' bytes') || true));
            
            $this->driver->getLoop()->futureTick(function () {
                $this->processBuffer();
            });
        }
    }
    
    /**
     * Adds the events to the connection.
     * @return void
     */
    protected function addEvents() {
        $this->connection->on('data', function ($chunk) {
            $this->buffer->append($chunk);
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
    }
}
