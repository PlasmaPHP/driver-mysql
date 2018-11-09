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
 * Query command.
 * @internal
 */
class QueryCommand extends PromiseCommand {
    /**
     * @var \Plasma\DriverInterface
     */
    protected $driver;
    
    /**
     * @var string
     */
    protected $query;
    
    /**
     * @var array|null
     */
    protected $fields = array();
    
    /**
     * @var int|null
     */
    protected $fieldsCount;
    
    /**
     * @var \Plasma\StreamQueryResult|null
     */
    protected $resolveValue;
    
    /**
     * Constructor.
     * @param \Plasma\DriverInterface  $driver
     * @param string                   $query
     */
    function __construct(\Plasma\DriverInterface $driver, string $query) {
        parent::__construct();
        
        $this->driver = $driver;
        $this->query = $query;
    }
    
    /**
     * Get the encoded message for writing to the database connection.
     * @return string
     */
    function getEncodedMessage(): string {
        return \chr(\Plasma\Drivers\MySQL\CommandConstants::CMD_QUERY).$this->query;
    }
    
    /**
     * Sends the next received value into the command.
     * @param mixed  $value
     * @return void
     */
    function onNext($value): void {
        if($value instanceof \Plasma\Drivers\MySQL\ProtocolOnNextCaller) {
            $parser = $value->getParser();
            $buffer = $value->getBuffer();
            
            if($fieldsParsed) {
                $row = array();
                
                /** @var \Plasma\ColumnDefinitionInterface  $column */
                foreach($this->fields as $column) {
                    $row[$column->getName()] = $column->parseValue(\Plasma\Drivers\MySQL\Messages\MessageUtility::readStringLength($buffer));
                }
                
                $this->emit('data', array($row));
            } else {
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
                
                $this->fields[$field->getName()] = static::parseColumnDefinition($buffer, $parser);
                
                if(\count($this->fields) >= $this->fieldsCount) {
                    $this->createResolve();
                }
            }
        } elseif($message instanceof \Plasma\Drivers\MySQL\Messages\OkResponseMessage || $message instanceof \Plasma\Drivers\MySQL\Messages\EOFMessage) {
            if($this->resolveValue !== null) {
                $parser->markCommandAsFinished($this);
            } elseif(empty($this->fields) && $message instanceof \Plasma\Drivers\MySQL\Messages\OkResponseMessage) {
                $this->resolveValue = new \Plasma\Drivers\MySQL\QueryResult($message->affectedRows, $message->warningsCount, $message->lastInsertedID);
                $parser->markCommandAsFinished($this);
            } else {
                $this->createResolve();
            }
        }
    }
    
    /**
     * Creates the resolve value and resolves the promise.
     * @return void
     */
    function createResolve(): void {
        $this->resolveValue = new \Plasma\StreamQueryResult($this->driver, $this, 0, 0, $this->fields, null);
        $this->deferred->resolve($this->resolveValue);
    }
}
