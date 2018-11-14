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
     * @var string
     */
    protected $buffer = '';
    
    /**
     * The sequence ID is incremented with each packet and may wrap around.
     * It starts at 0 and is reset to 0 when a new command begins in the Command Phase.
     * @var int
     * @see https://dev.mysql.com/doc/internals/en/sequence-id.html
     */
    protected $sequenceID = 0;
    
    /**
     * @var string[]
     */
    protected $messageClasses = array();
    
    /**
     * @var \Plasma\Drivers\MySQL\Messages\HandshakeMessage|null
     */
    protected $handshakeMessage;
    
    /**
     * @var \Plasma\CommandInterface|null
     */
    protected $currentCommand;
    
    /**
     * Constructor.
     * @param \Plasma\Drivers\MySQL\Driver       $driver
     * @param \React\Socket\ConnectionInterface  $connection
     */
    function __construct(\Plasma\Drivers\MySQL\Driver $driver, \React\Socket\ConnectionInterface $connection) {
        $this->driver = $driver;
        $this->connection = $connection;
        
        $this->registerMessages();
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
        $length = \Plasma\Drivers\MySQL\Messages\MessageUtility::writeInt3(\strlen($packet));
        $sequence = \Plasma\Drivers\MySQL\Messages\MessageUtility::writeInt1((++$this->sequenceID));
        
        $this->connection->write($length.$sequence.$packet);
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
        
        if($command instanceof \Plasma\Drivers\MySQL\Commands\CommandInterface && $command->resetSequence()) {
            $this->sequenceID = -1;
        }
        
        echo 'Processing command '.get_class($command).PHP_EOL;
        
        $this->sendPacket($command->getEncodedMessage());
        
        if($command !== $this->currentCommand || !$command->waitForCompletion()) {
            echo 'Mark command as completed'.PHP_EOL;
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
        $buffer = $this->buffer;
        $bufferLen = \strlen($this->buffer);
        
        $length = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt3($buffer);
        $this->sequenceID = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt1($buffer);
        
        if($bufferLen < $length) {
            echo 'returned (insufficent length)'.PHP_EOL;
            return;
        }
        
        if($this->currentCommand instanceof \Plasma\Drivers\MySQL\Commands\PromiseCommand) {
            var_dump(unpack('C*', $buffer));
        }
        
        /** @var \Plasma\Drivers\MySQL\Messages\MessageInterface  $message */
        $message = null;
        
        if($this->state === static::STATE_INIT) {
            $message = new \Plasma\Drivers\MySQL\Messages\HandshakeMessage($this);
        } else {
            $firstChar = \Plasma\Drivers\MySQL\Messages\MessageUtility::readBuffer($buffer, 1);
            echo 'Received Message char "'.$firstChar.'" (0x'.dechex(ord($firstChar)).')'.PHP_EOL;
            
            if($firstChar === "\xFE" && $this->state < static::STATE_OK) {
                $message = new \Plasma\Drivers\MySQL\Messages\AuthSwitchRequestMessage($this);
            } elseif(
                isset($this->messageClasses[$firstChar]) &&
                ($firstChar !== \Plasma\Drivers\MySQL\Messages\EOFMessage::getID() || $length < 6) &&
                ($firstChar !== \Plasma\Drivers\MySQL\Messages\AuthMoreDataMessage::getID() || $this->state < static::STATE_OK)
            ) {
                $cl = $this->messageClasses[$firstChar];
                $message = new $cl($this);
            } elseif($this->state === static::STATE_OK && $this->currentCommand !== null) {
                $buffer = $firstChar.$buffer;
                
                $caller = new \Plasma\Drivers\MySQL\ProtocolOnNextCaller($this, $buffer);
                $this->currentCommand->onNext($caller);
            }
        }
        
        if(!($message instanceof \Plasma\Drivers\MySQL\Messages\MessageInterface)) {
            echo 'returned (no message)'.PHP_EOL;
            $this->buffer = $buffer;
            
            if(\strlen($this->buffer) > 0) {
                $this->processBuffer();
            }
            
            return;
        }
        
        echo 'Received Message '.get_class($message).PHP_EOL;
        
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
     * @param string                                           $buffer
     * @param \Plasma\Drivers\MySQL\Messages\MessageInterface  $message
     * @return void
     */
    function handleMessage(string &$buffer, \Plasma\Drivers\MySQL\Messages\MessageInterface $message) {
        try {
            $buffer = $message->parseMessage($buffer, $this);
            if($buffer === false) {
                echo 'returned handle (unsufficent buffer length)'.PHP_EOL;
                return;
            }
            
            $this->buffer = $buffer;
            
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
                $this->buffer = $buffer;
            }
            
            if($this->currentCommand !== null) {
                $this->currentCommand->onError($e);
            }
            
            $this->emit('error', array($e));
            $this->connection->close();
        }
    }
    
    /**
     * Registers the message classes.
     * @return void
     */
    protected function registerMessages() {
        $classes = array(
            (\Plasma\Drivers\MySQL\Messages\AuthMoreDataMessage::getID()) => \Plasma\Drivers\MySQL\Messages\AuthMoreDataMessage::class,
            (\Plasma\Drivers\MySQL\Messages\EOFMessage::getID()) => \Plasma\Drivers\MySQL\Messages\EOFMessage::class,
            (\Plasma\Drivers\MySQL\Messages\ErrResponseMessage::getID()) => \Plasma\Drivers\MySQL\Messages\ErrResponseMessage::class,
            (\Plasma\Drivers\MySQL\Messages\OkResponseMessage::getID()) => \Plasma\Drivers\MySQL\Messages\OkResponseMessage::class
        );
        
        foreach($classes as $id => $class) {
            $this->messageClasses[$id] = $class;
        }
    }
    
    /**
     * Adds the events to the connection.
     * @return void
     */
    protected function addEvents() {
        $this->connection->on('data', function ($chunk) {
            $this->buffer .= $chunk;
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
