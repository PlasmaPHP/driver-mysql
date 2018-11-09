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
        \Plasma\Drivers\MySQL\ConnectionFlags::CLIENT_LONG_PASSWORD |
        \Plasma\Drivers\MySQL\ConnectionFlags::CLIENT_LONG_FLAG |
        \Plasma\Drivers\MySQL\ConnectionFlags::CLIENT_LOCAL_FILES |
        \Plasma\Drivers\MySQL\ConnectionFlags::CLIENT_INTERACTIVE |
        \Plasma\Drivers\MySQL\ConnectionFlags::CLIENT_TRANSACTIONS |
        \Plasma\Drivers\MySQL\ConnectionFlags::CLIENT_SECURE_CONNECTION |
        \Plasma\Drivers\MySQL\ConnectionFlags::CLIENT_DEPRECATE_EOF
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
    protected $state = static::STATE_INIT;
    
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
     * @var \Plasma\Drivers\MySQL\Commands\CommandInterface|null
     */
    protected $currentCommand;
    
    function __construct(\Plasma\Drivers\MySQL\Driver $driver, \React\Socket\ConnectionInterface $connection) {
        $this->driver = $driver;
        $this->connection = $connection;
        
        $this->registerMessages();
        $this->addEvents();
    }
    
    /**
     * Invoke a command to execute.
     * @param \Plasma\Drivers\MySQL\Commands\CommandInterface  $command
     * @return void
     */
    function invokeCommand(?\Plasma\Drivers\MySQL\Commands\CommandInterface $command): void {
        if($command === null) {
            return;
        }
        
        $this->currentCommand = $command;
        $this->processCommand();
    }
    
    /**
     * Processes a command.
     * @return void
     */
    protected function processCommand() {
        if(!$this->currentCommand) {
            return;
        }
        
        $state = $this->currentCommand->setParserState();
        if($state !== -1) {
            $this->state = $state;
        }
    }
    
    /**
     * Processes the buffer.
     * @return void
     */
    protected function processBuffer() {
        $firstChar = \Plasma\Drivers\MySQL\Messages\MessageUtility::readBuffer($this->buffer, 1);
        
        if(isset($this->messageClasses[$firstChar]) || $this->state === static::STATE_INIT) {
            $cl = $this->messageClasses[$firstChar] ?? \Plasma\Drivers\MySQL\Messages\HandshakeMessage::class;
            
            /** @var \Plasma\Drivers\MySQL\Messages\MessageInterface  $message */
            $message = new $cl();
            
            $state = $message->setParserState();
            if($state !== -1) {
                $this->state = $state;
            }
            
            try {
                $this->buffer = $message->parseMessage($this->buffer);
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
                
                $this->emit('error', array($e));
                $this->connection->close();
            }
        }
    }
    
    /**
     * Registers the message classes.
     * @return void
     */
    protected function registerMessages() {
        $files = \glob(__DIR__.'/Messages/*.php');
        foreach($files as $file) {
            $name = \basename($file, '.php');
            $clname = '\\Plasma\\Drivers\\MySQL\\'.$name;
            
            if(\class_exists($clname, true) && \in_array(\Plasma\Drivers\MySQL\Messages\MessageInterface::class, \class_implements($clname))) {
                $this->messageClasses[$clname::getID()] = $clname;
            }
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
        
        $this->connection->on('error', function () {
            $this->handleError();
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
    
    /**
     * Connection error handler.
     * @param \Throwable  $error
     * @return void
     */
    protected function handleError(\Throwable $error) {
        
    }
}
