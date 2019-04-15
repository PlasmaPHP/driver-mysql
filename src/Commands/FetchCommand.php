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
     * @var \Plasma\Drivers\MySQL\StatementCursor
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
     * @param \Plasma\DriverInterface                $driver
     * @param \Plasma\Drivers\MySQL\StatementCursor  $cursor
     * @param mixed                                  $id
     * @param string                                 $query
     * @param array                                  $params
     * @param \Plasma\ColumnDefinitionInterface[]    $paramsDef
     * @param int                                    $amount
     */
    function __construct(\Plasma\DriverInterface $driver, \Plasma\Drivers\MySQL\StatementCursor $cursor, $id, string $query, array $params, array $paramsDef, int $amount) {
        parent::__construct($driver, $id, $query, $params, $paramsDef, 0);
        
        $this->cursor = $cursor;
        $this->amount = $amount;
        
        $this->on('data', function ($row) {
            $this->rows[] = $row;
        });
        
        $this->removeAllListeners('end');
        $this->once('end', function () {
            // Let the event loop read the stream buffer before resolving
            $this->driver->getLoop()->futureTick(function () {
                // Unwrap if we only have one row
                $rows = (\count($this->rows) === 1 ? \reset($this->rows) : $this->rows);
                
                $this->deferred->resolve($this->rows);
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
        $packet .= \Plasma\BinaryBuffer::writeInt4($this->id);
        $packet .= \Plasma\BinaryBuffer::writeInt4($this->amount);
        
        return $packet;
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
            $this->cursor->processOkMessage($value);
            $value->getParser()->markCommandAsFinished($this);
        }
    }
}
