<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
 */

namespace Plasma\Drivers\MySQL\Commands;

use Evenement\EventEmitterTrait;
use Plasma\BinaryBuffer;
use Plasma\ColumnDefinitionInterface;
use Plasma\Drivers\MySQL\CharacterSetFlags;
use Plasma\Drivers\MySQL\ColumnDefinition;
use Plasma\Drivers\MySQL\Driver;
use Plasma\Drivers\MySQL\FieldFlags;
use Plasma\Drivers\MySQL\Messages\ErrResponseMessage;
use Plasma\Drivers\MySQL\Messages\LocalInFileRequestMessage;
use Plasma\Drivers\MySQL\Messages\OkResponseMessage;
use Plasma\Drivers\MySQL\ProtocolParser;
use Plasma\Drivers\MySQL\TextProtocolValues;
use Plasma\Exception;
use Plasma\Types\TypeExtensionsManager;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;

/**
 * Abstract command which has a promise.
 * @internal
 */
abstract class PromiseCommand implements CommandInterface {
    use EventEmitterTrait;
    
    /**
     * @var Driver
     */
    protected $driver;
    
    /**
     * @var Deferred
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
     * @param Driver  $driver
     */
    function __construct(Driver $driver) {
        $this->driver = $driver;
        $this->deferred = new Deferred();
        
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
     * @return PromiseInterface
     */
    function getPromise(): PromiseInterface {
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
     * @param BinaryBuffer    $buffer
     * @param ProtocolParser  $parser
     * @return ColumnDefinitionInterface|null
     */
    function handleQueryOnNextCallerColumns(BinaryBuffer $buffer, ProtocolParser $parser): ?ColumnDefinitionInterface {
        if($this->fieldsCount === null) {
            $fieldCount = $buffer->readIntLength();
            
            if($fieldCount === 0x00) {
                $this->driver->getLoop()->futureTick(function () use (&$buffer, &$parser) {
                    $msg = new OkResponseMessage($parser);
                    $parser->handleMessage($buffer, $msg);
                });
                
                return null;
            } elseif($fieldCount === 0xFB) {
                // Handle it on future tick, so we can cleanly finish the buffer of this call
                $this->driver->getLoop()->futureTick(function () use (&$buffer, &$parser) {
                    $msg = new LocalInFileRequestMessage($parser);
                    $parser->handleMessage($buffer, $msg);
                });
                
                return null;
            } elseif($fieldCount === 0xFF) {
                $this->driver->getLoop()->futureTick(function () use (&$buffer, &$parser) {
                    $msg = new ErrResponseMessage($parser);
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
     * @param BinaryBuffer  $buffer
     * @return ColumnDefinitionInterface
     */
    static function parseColumnDefinition(BinaryBuffer $buffer): ColumnDefinitionInterface {
        $buffer->readStringLength(); // catalog - always "def"
        $buffer->readStringLength(); // database
        
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
        
        $charset = CharacterSetFlags::CHARSET_MAP[$charset] ?? null;
        $type = FieldFlags::TYPE_MAP[$type] ?? 'Unknown type "'.$type.'"';
        
        return (new ColumnDefinition($table, $name, $type, $charset, $length, $flags, $decimals));
    }
    
    /**
     * Parses the text resultset row and returns the row.
     * @param BinaryBuffer  $buffer
     * @return array
     * @throws Exception
     */
    protected function parseResultsetRow(BinaryBuffer $buffer): array {
        $row = array();
    
        /** @noinspection PhpPossiblePolymorphicInvocationInspection */
        /** @var ColumnDefinitionInterface $column */
        foreach($this->fields as $column) {
            $rawValue = $buffer->readStringLength();
            
            try {
                $value = TypeExtensionsManager::getManager('driver-mysql')
                    ->decodeType($column->getType(), $rawValue)
                    ->getValue();
            } catch (Exception $e) {
                $value = TextProtocolValues::decode($column, $rawValue);
            }
            
            $row[$column->getName()] = $value;
        }
        
        return $row;
    }
}
