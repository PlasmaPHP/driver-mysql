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
use Plasma\ColumnDefinitionInterface;
use Plasma\Drivers\MySQL\BinaryProtocolValues;
use Plasma\Drivers\MySQL\Driver;
use Plasma\Drivers\MySQL\Messages\EOFMessage;
use Plasma\Drivers\MySQL\Messages\OkResponseMessage;
use Plasma\Drivers\MySQL\StatusFlags;
use Plasma\Exception;
use Plasma\Types\TypeExtensionsManager;

/**
 * Statement Execute command.
 * @internal
 */
class StatementExecuteCommand extends QueryCommand {
    /**
     * The identifier for this command.
     * @var int
     * @source
     */
    const COMMAND_ID = 0x17;
    
    /**
     * @var mixed
     */
    protected $id;
    
    /**
     * @var array
     */
    protected $params;
    
    /**
     * @var ColumnDefinitionInterface[]
     */
    protected $paramsDef;
    
    /**
     * @var int
     */
    protected $cursor;
    
    /**
     * Constructor.
     * @param Driver                       $driver
     * @param mixed                        $id
     * @param string                       $query
     * @param array                        $params
     * @param ColumnDefinitionInterface[]  $paramsDef
     * @param int                          $cursor
     */
    function __construct(Driver $driver, $id, string $query, array $params, array $paramsDef, int $cursor = 0) {
        parent::__construct($driver, $query);
        
        $this->id = $id;
        $this->params = $params;
        $this->paramsDef = $paramsDef;
        $this->cursor = $cursor;
    }
    
    /**
     * Get the encoded message for writing to the database connection.
     * @return string
     * @throws Exception
     */
    function getEncodedMessage(): string {
        $packet = \chr(static::COMMAND_ID);
        $packet .= BinaryBuffer::writeInt4($this->id);
        
        $packet .= \chr($this->cursor);
        $packet .= BinaryBuffer::writeInt4(1); // Iteration count is always 1
        
        $bitmapOffset = \strlen($packet);
        $packet .= \str_repeat("\x00", ((\count($this->params) + 7) >> 3));
        
        $bound = 0;
        
        $types = '';
        $values = '';
        
        foreach($this->params as $id => $param) {
            if($param === null) {
                $offset = $bitmapOffset + ($id >> 3);
                $packet[$offset] = $packet[$offset] | \chr((1 << ($id % 8)));
            } else {
                $bound = 1;
            }
            
            try {
                $encode = TypeExtensionsManager::getManager('driver-mysql')
                    ->encodeType($param, $this->paramsDef[$id]);
                
                $unsigned = $encode->isUnsigned();
                $type = $encode->getDatabaseType();
                $value = $encode->getValue();
            } catch (Exception $e) {
                [$unsigned, $type, $value] = BinaryProtocolValues::encode($param);
            }
            
            $types .= \chr($type).($unsigned ? "\x80" : "\x00");
            $values .= $value;
        }
        
        $packet .= \chr($bound);
        
        if($bound) {
            $packet .= $types;
            $packet .= $values;
        }
        
        return $packet;
    }
    
    /**
     * Sends the next received value into the command.
     * @param mixed  $value
     * @return void
     * @throws Exception
     */
    function onNext($value): void {
        if($this->cursor > 0 && $this->fieldsCount > 0 && $this->fieldsCount <= \count($this->fields)) {
            if(!($value instanceof OkResponseMessage || $value instanceof EOFMessage)) {
                throw new Exception('Requested a cursor, but received row instead');
            } elseif(($value->statusFlags & StatusFlags::SERVER_STATUS_CURSOR_EXISTS) === 0) {
                throw new Exception('Requested a cursor, but did not receive one');
            }
        }
        
        parent::onNext($value);
    }
    
    /**
     * Parses the binary resultset row and returns the row.
     * @param BinaryBuffer  $buffer
     * @return array
     * @throws Exception
     */
    protected function parseResultsetRow(BinaryBuffer $buffer): array {
        $buffer->read(1); // remove packet header
        
        $nullRow = array();
        $i = 0;
        
        foreach($this->fields as $column) { // Handle NULL-bitmap
            if((\ord($buffer[(($i + 2) >> 3)]) & (1 << (($i + 2) % 8))) !== 0) {
                $nullRow[$column->getName()] = null;
            }
            
            $i++;
        }
        
        $buffer->read(((\count($this->fields) + 9) >> 3)); // Remove NULL-bitmap
        $row = array();
        
        foreach($this->fields as $column) {
            if(\array_key_exists($column->getName(), $nullRow)) {
                $row[$column->getName()] = null;
                continue;
            }
            
            try {
                $value = TypeExtensionsManager::getManager('driver-mysql')
                    ->decodeType($column->getType(), $buffer)
                    ->getValue();
            } catch (Exception $e) {
                $value = BinaryProtocolValues::decode($column, $buffer);
            }
            
            $row[$column->getName()] = $value;
        }
        
        return $row;
    }
}
