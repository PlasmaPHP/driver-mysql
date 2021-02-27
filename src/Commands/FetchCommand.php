<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
 */

namespace Plasma\Drivers\MySQL\Commands;

use Plasma\BinaryBuffer;
use Plasma\Exception;
use Plasma\ColumnDefinitionInterface;
use Plasma\Drivers\MySQL\Driver;
use Plasma\Drivers\MySQL\Messages\EOFMessage;
use Plasma\Drivers\MySQL\Messages\OkResponseMessage;
use Plasma\Drivers\MySQL\ProtocolOnNextCaller;
use Plasma\Drivers\MySQL\StatementCursor;

/**
 * Fetch command.
 * @internal
 */
class FetchCommand extends StatementExecuteCommand {
    /**
     * The identifier for this command.
     * @var int
     * @source
     */
    const COMMAND_ID = 0x1C;
    
    /**
     * @var StatementCursor
     */
    protected $cursor;
    
    /**
     * @var int
     */
    protected $amount;
    
    /**
     * @var array
     */
    protected $rows = array();
    
    /**
     * Constructor.
     * @param Driver                       $driver
     * @param StatementCursor              $cursor
     * @param mixed                        $id
     * @param string                       $query
     * @param array                        $params
     * @param ColumnDefinitionInterface[]  $paramsDef
     * @param ColumnDefinitionInterface[]  $fields
     * @param int                          $amount
     */
    function __construct(
        Driver $driver,
        StatementCursor $cursor,
        $id,
        string $query,
        array $params,
        array $paramsDef,
        array $fields,
        int $amount
    ) {
        parent::__construct($driver, $id, $query, $params, $paramsDef, 0);
        
        $this->cursor = $cursor;
        $this->fields = $fields;
        $this->amount = $amount;
        
        $this->on(
            'data',
            function ($row) {
                $this->rows[] = $row;
            }
        );
        
        $this->removeAllListeners('end');
        $this->once('end',function () {
            // Let the event loop read the stream buffer before resolving
            $this->driver->getLoop()->futureTick(function () {
                // Unwrap if we only have one row
                $crows = \count($this->rows);
                $rows = ($crows === 1 ? \reset($this->rows) : ($crows === 0 ? false : $this->rows));
                
                $this->deferred->resolve($rows);
                $this->rows = null;
            });
        });
    }
    
    /**
     * Get the encoded message for writing to the database connection.
     * @return string
     */
    function getEncodedMessage(): string {
        $packet = \chr(static::COMMAND_ID);
        $packet .= BinaryBuffer::writeInt4($this->id);
        $packet .= BinaryBuffer::writeInt4($this->amount);
        
        return $packet;
    }
    
    /**
     * Sends the next received value into the command.
     * @param mixed  $value
     * @return void
     * @throws Exception
     */
    function onNext($value): void {
        if($value instanceof ProtocolOnNextCaller) {
            $this->handleQueryOnNextCaller($value);
        } elseif($value instanceof OkResponseMessage || $value instanceof EOFMessage) {
            $this->cursor->processOkMessage($value);
            $value->getParser()->markCommandAsFinished($this);
        }
    }
}
