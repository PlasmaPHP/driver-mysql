<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
 */

namespace Plasma\Drivers\MySQL\Commands;

use Plasma\ClientInterface;
use Plasma\ColumnDefinitionInterface;
use Plasma\Drivers\MySQL\CapabilityFlags;
use Plasma\Drivers\MySQL\Driver;
use Plasma\Drivers\MySQL\Messages\EOFMessage;
use Plasma\Drivers\MySQL\Messages\OkResponseMessage;
use Plasma\Drivers\MySQL\Messages\ParseException;
use Plasma\Drivers\MySQL\Messages\PrepareStatementOkMessage;
use Plasma\Drivers\MySQL\ProtocolOnNextCaller;
use Plasma\Drivers\MySQL\Statement;
use Plasma\StatementInterface;
use Plasma\Utility;

/**
 * Statement Prepare command.
 * @internal
 */
class StatementPrepareCommand extends PromiseCommand {
    /**
     * The identifier for this command.
     * @var int
     * @source
     */
    const COMMAND_ID = 0x16;
    
    /**
     * @var ClientInterface
     */
    protected $client;
    
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
     * @var PrepareStatementOkMessage
     */
    protected $okResponse;
    
    /**
     * @var ColumnDefinitionInterface[]
     */
    protected $params = array();
    
    /**
     * @var ColumnDefinitionInterface[]
     */
    protected $fields = array();
    
    /**
     * @var bool
     */
    protected $paramsDone = false;
    
    /**
     * @var StatementInterface|null
     */
    protected $resolveValue;
    
    /**
     * @var bool
     */
    protected $deprecatedEOF;
    
    /**
     * Constructor.
     * @param ClientInterface  $client
     * @param Driver  $driver
     * @param string           $query
     */
    function __construct(ClientInterface $client, Driver $driver, string $query) {
        parent::__construct($driver);
        
        $this->client = $client;
        $this->driver = $driver;
        $this->query = $query;
        $this->deprecatedEOF = (($driver->getHandshake()->capability & CapabilityFlags::CLIENT_DEPRECATE_EOF) !== 0);
        
        ['query' => $this->rewrittenQuery, 'parameters' => $this->rewrittenParams] = Utility::parseParameters($this->query, '?');
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
     * @throws ParseException
     */
    function onNext($value): void {
        if($value instanceof PrepareStatementOkMessage) {
            $this->okResponse = $value;
            $this->fieldsCount = $this->okResponse->numColumns;
            $this->paramsDone = ($this->okResponse->numParams === 0);
            
            if($this->paramsDone && $this->fieldsCount === 0) {
                $this->createResolve();
            }
        } elseif($value instanceof ProtocolOnNextCaller) {
            $buffer = $value->getBuffer();
            $parser = $value->getParser();
            
            $parsed = $this->handleQueryOnNextCallerColumns($buffer, $parser);
            
            if($this->paramsDone) {
                /** @noinspection NullPointerExceptionInspection */
                $this->fields[$parsed->getName()] = $parsed;
            } else {
                $this->params[] = $parsed;
            }
            
            if($this->deprecatedEOF) {
                if($this->paramsDone && $this->fieldsCount <= \count($this->fields)) {
                    $this->createResolve();
                } elseif($this->resolveValue === null && $this->okResponse->numParams <= \count($this->params)) {
                    $this->paramsDone = true;
                    
                    if($this->fieldsCount === 0) {
                        $this->createResolve();
                    }
                }
            }
        } elseif(
            $value instanceof EOFMessage || $value instanceof OkResponseMessage
        ) {
            if(!$this->paramsDone) {
                $this->paramsDone = true;
                
                if($this->fieldsCount === 0) {
                    $this->createResolve();
                }
                
                return;
            }
            
            $this->createResolve();
        } else {
            throw new ParseException(
                'Command received value of type '
                .(\is_object($value) ? \get_class($value) : \gettype($value)).' it can not handle'
            );
        }
    }
    
    /**
     * Whether the sequence ID should be resetted.
     * @return bool
     */
    function resetSequence(): bool {
        return true;
    }
    
    /**
     * Creates the resolve value.
     * @return void
     */
    protected function createResolve(): void {
        $this->finished = true;
        
        $id = $this->okResponse->statementID;
        $queryr = $this->rewrittenQuery;
        $paramsr = $this->rewrittenParams;
        
        $this->resolveValue = new Statement(
            $this->client,
            $this->driver,
            $id,
            $this->query,
            $queryr,
            $paramsr,
            $this->params,
            $this->fields
        );
        $this->deferred->resolve($this->resolveValue);
    }
}
