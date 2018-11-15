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
 * Prepare command.
 * @internal
 */
class PrepareCommand extends PromiseCommand {
    /**
     * The identifier for this command.
     * @var int
     * @source
     */
    const COMMAND_ID = 0x16;
    
    /**
     * @var \Plasma\ClientInterface
     */
    protected $client;
    
    /**
     * @var \Plasma\DriverInterface
     */
    protected $driver;
    
    /**
     * @var string
     */
    protected $query;
    
    /**
     * @var string
     */
    protected $rewrittenQuery;
    
    /**
     * @var array
     */
    protected $rewrittenParams;
    
    /**
     * @var \Plasma\Drivers\MySQL\Messages\PrepareStatementOkMessage
     */
    protected $okResponse;
    
    /**
     * @var \Plasma\ColumnDefinitionInterface[]
     */
    protected $params = array();
    
    /**
     * @var \Plasma\ColumnDefinitionInterface[]
     */
    protected $fields = array();
    
    /**
     * @var int
     */
    protected $fieldsCount;
    
    /**
     * @var \Plasma\StatementInterface|null
     */
    protected $resolveValue;
    
    /**
     * Constructor.
     * @param \Plasma\ClientInterface  $client
     * @param \Plasma\DriverInterface  $driver
     * @param string                   $query
     */
    function __construct(\Plasma\ClientInterface $client, \Plasma\DriverInterface $driver, string $query) {
        parent::__construct();
        
        $this->client = $client;
        $this->driver = $driver;
        $this->query = $query;
        
        [ 'query' => $this->rewrittenQuery, 'parameters' => $this->rewrittenParams ] = \Plasma\Utility::parseParameters($this->query, '?');
    }
    
    /**
     * Get the encoded message for writing to the database connection.
     * @return string
     */
    function getEncodedMessage(): string {
        return \chr(static::COMMAND_ID).$this->rewrittenQuery;
    }
    
    /**
     * Sends the next received value into the command.
     * @param mixed  $value
     * @return void
     * @throws \Plasma\Drivers\MySQL\Messages\ParseException
     */
    function onNext($value): void {
        var_dump($value); ob_flush();
        
        if($value instanceof \Plasma\Drivers\MySQL\Messages\PrepareStatementOkMessage) {
            $this->okResponse = $value;
            $this->fieldsCount = $this->okResponse->numColumns;
        } elseif($value instanceof \Plasma\Drivers\MySQL\ProtocolOnNextCaller) {
            $buffer = $value->getBuffer();
            $parser = $value->getParser();
            
            $parsed = $this->handleQueryOnNextCallerColumns($buffer, $parser);
            
            if($this->okResponse->numParams >= \count($this->params)) {
                $this->params[] = $parsed;
            } elseif($this->okResponse->numColumns >= \count($this->fields)) {
                $this->fields[$parsed->getName()] = $parsed;
            } else {
                throw new \Plasma\Drivers\MySQL\Messages\ParseException('Command received more column definition packets than defined');
            }
            
            $value->setBuffer($buffer);
        } elseif(
            $value instanceof \Plasma\Drivers\MySQL\Messages\EOFMessage || $value instanceof \Plasma\Drivers\MySQL\Messages\OkResponseMessage
        ) {
            $this->finished = true;
            
            $id = $this->okResponse->statementID;
            $queryr = $this->rewrittenQuery;
            $paramsr = $this->rewrittenParams;
            
            $this->resolveValue = new \Plasma\Drivers\MySQL\Statement($this->client, $this->driver, $id, $this->query, $queryr, $paramsr, $this->params, $this->fields);
            $this->deferred->resolve($this->resolveValue);
        } else {
            throw new \Plasma\Drivers\MySQL\Messages\ParseException('Command received value of type '
                .(\is_object($value) ? \get_class($value) : \gettype($value)).' it can not handle');
        }
    }
    
    /**
     * Whether the sequence ID should be resetted.
     * @return bool
     */
    function resetSequence(): bool {
        return true;
    }
}
