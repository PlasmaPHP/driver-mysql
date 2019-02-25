<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL\Commands;

/**
 * Abstract command which has a promise.
 * @internal
 */
abstract class PromiseCommand implements CommandInterface {
    use \Evenement\EventEmitterTrait;
    
    /**
     * @var \Plasma\DriverInterface
     */
    protected $driver;
    
    /**
     * @var \React\Promise\Deferred
     */
    protected $deferred;
    
    /**
     * @var mixed
     */
    protected $resolveValue;
    
    /**
     * @var bool
     */
    protected $finished = false;
    
    /**
     * @var int|null
     */
    protected $fieldsCount;
    
    /**
     * Constructor.
     * @param \Plasma\DriverInterface  $driver
     */
    function __construct(\Plasma\DriverInterface $driver) {
        $this->driver = $driver;
        $this->deferred = new \React\Promise\Deferred();
        
        $this->once('error', function (\Throwable $error) {
            $this->deferred->reject($error);
        });
        
        $this->once('end', function () {
            // Let the event loop read the stream buffer before resolving
            $this->driver->getLoop()->futureTick(function () {
                $this->deferred->resolve($this->resolveValue);
                $this->resolveValue = null;
            });
        });
    }
    
    /**
     * Get the encoded message for writing to the database connection.
     * @return string
     */
    abstract function getEncodedMessage(): string;
    
    /**
     * Get the promise.
     * @return \React\Promise\PromiseInterface
     */
    function getPromise(): \React\Promise\PromiseInterface {
        return $this->deferred->promise();
    }
    
    /**
     * Sets the parser state, if necessary. If not, return `-1`.
     * @return int
     */
    function setParserState(): int {
        return -1;
    }
    
    /**
     * Sets the command as completed. This state gets reported back to the user.
     * @return void
     */
    function onComplete(): void {
        $this->finished = true;
        $this->emit('end');
    }
    
    /**
     * Sets the command as errored. This state gets reported back to the user.
     * @param \Throwable  $throwable
     * @return void
     */
    function onError(\Throwable $throwable): void {
        $this->finished = true;
        
        if($this->resolveValue !== null) {
            // Let the event loop read the stream buffer and resolve the promise before emitting
            // Works around a race condition, where the promise resolves
            // after receiving an error response packet
            // and therefore the user misses the error event
            $this->driver->getLoop()->futureTick(function () use (&$throwable) {
                $this->emit('error', array($throwable));
            });
            
            return;
        }
        
        $this->emit('error', array($throwable));
    }
    
    /**
     * Sends the next received value into the command.
     * @param mixed  $value
     * @return void
     */
    abstract function onNext($value): void;
    
    /**
     * Whether the command has finished.
     * @return bool
     */
    function hasFinished(): bool {
        return $this->finished;
    }
    
    /**
     * Whether this command sets the connection as busy.
     * @return bool
     */
    function waitForCompletion(): bool {
        return true;
    }
    
    /**
     * Whether the sequence ID should be resetted.
     * @return bool
     */
    abstract function resetSequence(): bool;
    
    /**
     * Handles the column definitions of query commands on next caller.
     * @param \Plasma\BinaryBuffer                  $buffer
     * @param \Plasma\Drivers\MySQL\ProtocolParser  $parser
     * @return \Plasma\ColumnDefinitionInterface|null
     */
    function handleQueryOnNextCallerColumns(\Plasma\BinaryBuffer $buffer, \Plasma\Drivers\MySQL\ProtocolParser $parser): ?\Plasma\ColumnDefinitionInterface {
        if($this->fieldsCount === null) {
            $fieldCount = $buffer->readIntLength();
            
            if($fieldCount === 0x00) {
                $this->driver->getLoop()->futureTick(function () use (&$buffer, &$parser) {
                    $msg = new \Plasma\Drivers\MySQL\Messages\OkResponseMessage($parser);
                    $parser->handleMessage($buffer, $msg);
                });
                
                return null;
            } elseif($fieldCount === 0xFB) {
                // Handle it on future tick, so we can cleanly finish the buffer of this call
                $this->driver->getLoop()->futureTick(function () use (&$buffer, &$parser) {
                    $msg = new \Plasma\Drivers\MySQL\Messages\LocalInFileRequestMessage($parser);
                    $parser->handleMessage($buffer, $msg);
                });
                
                return null;
            } elseif($fieldCount === 0xFF) {
                $this->driver->getLoop()->futureTick(function () use (&$buffer, &$parser) {
                    $msg = new \Plasma\Drivers\MySQL\Messages\ErrResponseMessage($parser);
                    $parser->handleMessage($buffer, $msg);
                });
                
                return null;
            }
            
            $this->fieldsCount = $fieldCount;
            return null;
        }
        
        return static::parseColumnDefinition($buffer);
    }
    
    /**
     * Parses the column definition.
     * @param \Plasma\BinaryBuffer  $buffer
     * @return \Plasma\ColumnDefinitionInterface
     */
    static function parseColumnDefinition(\Plasma\BinaryBuffer $buffer): \Plasma\ColumnDefinitionInterface {
        $buffer->readStringLength(); // catalog - always "def"
        $database = $buffer->readStringLength();
        
        $table = $buffer->readStringLength();
        $buffer->readStringLength(); // orgTable
        
        $name = $buffer->readStringLength();
        $buffer->readStringLength(); // orgName
        
        $buffer->read(1); // 0x0C
        
        $charset = $buffer->readInt2();
        $length = $buffer->readInt4();
        $type = $buffer->readInt1();
        $flags = $buffer->readInt2();
        $decimals = $buffer->readInt1();
        
        $buffer->read(2); // fillers
        
        /*if($this instanceof COM_FIELD_LIST) {
            $buffer->readStringLength();
        }*/
        
        $charset = \Plasma\Drivers\MySQL\CharacterSetFlags::CHARSET_MAP[$charset] ?? 'Unknown charset "'.$charset.'"';
        $type = \Plasma\Drivers\MySQL\FieldFlags::TYPE_MAP[$type] ?? 'Unknown type "'.$type.'"';
        
        return (new \Plasma\Drivers\MySQL\ColumnDefinition($database, $table, $name, $type, $charset, $length, $flags, $decimals));
    }
    
    /**
     * Parses the text resultset row and returns the row.
     * @param \Plasma\BinaryBuffer  $buffer
     * @return array
     */
    protected function parseResultsetRow(\Plasma\BinaryBuffer $buffer): array {
        $row = array();
        
        /** @var \Plasma\ColumnDefinitionInterface  $column */
        foreach($this->fields as $column) {
            $rawValue = $buffer->readStringLength();
            
            try {
                $value = \Plasma\Types\TypeExtensionsManager::getManager('driver-mysql')
                    ->decodeType($column->getType(), $rawValue)
                    ->getValue();
            } catch (\Plasma\Exception $e) {
                $value = \Plasma\Drivers\MySQL\TextProtocolValues::decode($column, $rawValue);
            }
            
            $row[$column->getName()] = $value;
        }
        
        return $row;
    }
}
