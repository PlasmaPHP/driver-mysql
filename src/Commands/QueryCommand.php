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
     * The identifier for this command.
     * @var int
     * @source
     */
    const COMMAND_ID = 0x03;
    
    /**
     * @var string
     */
    protected $query;
    
    /**
     * @var \Plasma\ColumnDefinitionInterface[]
     */
    protected $fields = array();
    
    /**
     * @var int|null
     */
    protected $fieldsCount;
    
    /**
     * @var \Plasma\StreamQueryResult|\Plasma\QueryResult|null
     */
    protected $resolveValue;
    
    /**
     * Constructor.
     * @param \Plasma\DriverInterface  $driver
     * @param string                   $query
     */
    function __construct(\Plasma\DriverInterface $driver, string $query) {
        parent::__construct($driver);
        
        $this->driver = $driver;
        $this->query = $query;
    }
    
    /**
     * Get the encoded message for writing to the database connection.
     * @return string
     */
    function getEncodedMessage(): string {
        return \chr(static::COMMAND_ID).$this->query;
    }
    
    /**
     * Sends the next received value into the command.
     * @param mixed  $value
     * @return void
     */
    function onNext($value): void {
        if($value instanceof \Plasma\Drivers\MySQL\ProtocolOnNextCaller) {
            $this->handleQueryOnNextCaller($value);
        } elseif($value instanceof \Plasma\Drivers\MySQL\Messages\OkResponseMessage || $value instanceof \Plasma\Drivers\MySQL\Messages\EOFMessage) {
            if($this->resolveValue !== null) {
                $value->getParser()->markCommandAsFinished($this);
            } elseif($this->fieldsCount == 0 && $value instanceof \Plasma\Drivers\MySQL\Messages\OkResponseMessage) { // Matching 0 and null
                $this->resolveValue = new \Plasma\QueryResult($value->affectedRows, $value->warningsCount, $value->lastInsertedID);
                $value->getParser()->markCommandAsFinished($this);
            } else {
                $this->createResolve();
            }
        }
    }
    
    /**
     * Handles query commands on next caller.
     * @param \Plasma\Drivers\MySQL\ProtocolOnNextCaller  $value
     * @return void
     */
    function handleQueryOnNextCaller(\Plasma\Drivers\MySQL\ProtocolOnNextCaller $value): void {
        $buffer = $value->getBuffer();
        $parser = $value->getParser();
        
        if($this->resolveValue !== null) {
            $row = $this->parseResultsetRow($buffer);
            var_dump($row);
            $this->emit('data', array($row));
        } else {
            $field = $this->handleQueryOnNextCallerColumns($buffer, $parser);
            if($field) {
                $this->fields[$field->getName()] = $field;
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
    
    /**
     * Whether the sequence ID should be resetted.
     * @return bool
     */
    function resetSequence(): bool {
        return true;
    }
}
