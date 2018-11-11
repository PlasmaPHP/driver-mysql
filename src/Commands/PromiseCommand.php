<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018 PlasmaPHP, All Rights Reserved
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
     * Constructor.
     */
    function __construct() {
        $this->deferred = new \React\Promise\Deferred();
        
        $this->once('error', function (\Throwable $error) {
            $this->deferred->reject($error);
        });
        
        $this->once('end', function () {
            $this->deferred->resolve($this->resolveValue);
        });
    }
    
    /**
     * Get the encoded message for writing to the database connection.
     * @return string
     */
    abstract function getEncodedMessage(): string;
    
    /**
     * Get the parse callback.
     * @return callable
     */
    abstract function getPacketCallback(): callable;
    
    /**
     * Get the promise.
     * @var \React\Promise\PromiseInterface
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
        $this->emit('end', array());
    }
    
    /**
     * Sets the command as errored. This state gets reported back to the user.
     * @param \Throwable  $throwable
     * @return void
     */
    function onError(\Throwable $throwable): void {
        $this->finished = true;
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
     * Handles query commands on next caller.
     * @param \Plasma\Drivers\MySQL\ProtocolOnNextCaller  $value
     * @return void
     */
    function handleQueryOnNextCaller(\Plasma\Drivers\MySQL\ProtocolOnNextCaller $value): void {
        $parser = $value->getParser();
        $buffer = $value->getBuffer();
        
        if($this->resolveValue !== null) {
            $this->emit('data', array($this->parseResultsetRow($buffer)));
        } else {
            $this->fields[$field->getName()] = $this->handleQueryOnNextCallerColumns($value);
            
            if(\count($this->fields) >= $this->fieldsCount) {
                $this->createResolve();
            }
        }
    }
    
    /**
     * Handles the column definitions of query commands on next caller.
     * @param \Plasma\Drivers\MySQL\ProtocolOnNextCaller  $value
     * @return \Plasma\ColumnDefinitionInterface
     */
    function handleQueryOnNextCallerColumns(\Plasma\Drivers\MySQL\ProtocolOnNextCaller $value): \Plasma\ColumnDefinitionInterface {
        $parser = $value->getParser();
        $buffer = $value->getBuffer();
        
        $original = $buffer;
        $fieldCount = \Plasma\Drivers\MySQL\Messages\MessageUtility::readIntLength($buffer);
        
        if($this->fieldsCount === null) {
            if($fieldCount === 0xFB) {
                // Handle it on future tick, so we can cleanly finish the buffer of this call
                $this->driver->getLoop()->futureTick(function () use (&$parser) {
                    $localMsg = new \Plasma\Drivers\MySQL\Messages\LocalInFileDataMessage();
                    $parser->handleMessage($localMsg);
                });
                
                return;
            }
            
            $this->fieldsCount = $fieldCount;
            
            if(\strlen($buffer) === 0) {
                return;
            }
        } else {
            $buffer = $original;
            $original = null;
        }
        
        return static::parseColumnDefinition($buffer, $parser);
    }
    
    /**
     * Parses the column definition.
     * @param string                                $buffer
     * @param \Plasma\Drivers\MySQL\ProtocolParser  $parser
     * @return \Plasma\ColumnDefinitionInterface
     */
    static function parseColumnDefinition(string &$buffer, \Plasma\Drivers\MySQL\ProtocolParser $parser): \Plasma\ColumnDefinitionInterface {
        \Plasma\Drivers\MySQL\Messages\MessageUtility::readIntLength($buffer); // catalog
        $database = \Plasma\Drivers\MySQL\Messages\MessageUtility::readIntLength($buffer);
        $table = \Plasma\Drivers\MySQL\Messages\MessageUtility::readIntLength($buffer);
        \Plasma\Drivers\MySQL\Messages\MessageUtility::readIntLength($buffer); // org_table
        $name = \Plasma\Drivers\MySQL\Messages\MessageUtility::readIntLength($buffer);
        \Plasma\Drivers\MySQL\Messages\MessageUtility::readIntLength($buffer); // org_name
        
        $buffer = \substr($buffer, 1); // 0x0C
        
        $charset = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt2($buffer);
        $length = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt4($buffer);
        $type = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt1($buffer);
        $flags = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt2($buffer);
        $decimals = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt1($buffer);
        
        $buffer = \substr($buffer, 2); // fillers
        
        /*if($this instanceof COM_FIELD_LIST) {
            \Plasma\Drivers\MySQL\Messages\MessageUtility::readStringLength($buffer);
        }*/
        
        $charset = \Plasma\Drivers\MySQL\CharacterSetFlags::CHARSET_MAP[$charset];
        $type = $type;
        $nullable = ($flags & \Plasma\Drivers\MySQL\FieldFlags::NOT_NULL_FLAG) === 0;
        
        return (new \Plasma\ColumnDefinition($database, $table, $name, $type, $charset, $length, $nullable, $flags, $decimals));
    }
    
    /**
     * Parses the text resultset row and returns the row.
     * @param \Plasma\ColumnDefinitionInterface  $column
     * @param string                             $buffer
     * @return array
     */
    protected function parseResultsetRow(string &$buffer): array {
        $row = array();
        
        /** @var \Plasma\ColumnDefinitionInterface  $column */
        foreach($this->fields as $column) {
            $rawValue = \Plasma\Drivers\MySQL\Messages\MessageUtility::readStringLength($buffer);
            
            try {
                $value = $column->parseValue($rawValue);
            } catch (\Plasma\Exception $e) {
                $value = $this->stdDecodeValue($rawValue);
            }
            
            $row[$column->getName()] = $value;
        }
        
        return $row;
    }
    
    /**
     * Standard decode value, if type extensions failed.
     * @param \Plasma\ColumnDefinitionInterface  $column
     * @param string                             $param
     * @return mixed
     * @throws \Plasma\Exception
     */
    protected function stdDecodeValue(\Plasma\ColumnDefinitionInterface $column, string $param) {
        $flags = $column->getFlags();
        
        if($param !== null && ($flags & \Plasma\Drivers\MySQL\FieldFlags::ZEROFILL_FLAG) === 0) {
            switch($column->getType()) {
                case \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_LONG:
                    if(($flags & \Plasma\Drivers\MySQL\FieldFlags::UNSIGNED_FLAG) === 0 || \PHP_INT_SIZE > 4) {
                        $param = (int) $param;
                    }
                break;
                case \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_LONGLONG:
                    if(($flags & \Plasma\Drivers\MySQL\FieldFlags::UNSIGNED_FLAG) === 0 && \PHP_INT_SIZE > 4) {
                        $param = (int) $param;
                    }
                break;
                case \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_TINY:
                case \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_SHORT:
                case \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_INT24:
                case \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_TIMESTAMP:
                    $param = (int) $param;
                break;
                case \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_FLOAT:
                case \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_DOUBLE:
                    $param = (float) $param;
                break;
                // Other types are taken as string
            }
        }
        
        return $param;
    }
}
