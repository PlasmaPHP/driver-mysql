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
     * @var \Plasma\DriverInterface
     */
    protected $driver;
    
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
        parent::__construct();
        
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
            } elseif(empty($this->fields) && $value instanceof \Plasma\Drivers\MySQL\Messages\OkResponseMessage) {
                $this->resolveValue = new \Plasma\QueryResult($value->affectedRows, $value->warningsCount, $value->lastInsertedID);
                $value->getParser()->markCommandAsFinished($this);
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
    
    /**
     * Whether the sequence ID should be resetted.
     * @return bool
     */
    function resetSequence(): bool {
        return true;
    }
}
