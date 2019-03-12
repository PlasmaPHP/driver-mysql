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
     * @var \Plasma\ColumnDefinitionInterface[]
     */
    protected $paramsDef;
    
    /**
     * Constructor.
     * @param \Plasma\DriverInterface              $driver
     * @param mixed                                $id
     * @param string                               $query
     * @param array                                $params
     * @param \Plasma\ColumnDefinitionInterface[]  $paramsDef
     */
    function __construct(\Plasma\DriverInterface $driver, $id, string $query, array $params, array $paramsDef) {
        parent::__construct($driver, $query);
        
        $this->id = $id;
        $this->params = $params;
        $this->paramsDef = $paramsDef;
    }
    
    /**
     * Get the encoded message for writing to the database connection.
     * @return string
     */
    function getEncodedMessage(): string {
        $packet = \chr(static::COMMAND_ID);
        $packet .= \Plasma\BinaryBuffer::writeInt4($this->id);
        
        $packet .= "\x00"; // Cursor type flag
        $packet .= \Plasma\BinaryBuffer::writeInt4(1); // Iteration count is always 1
        
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
                $encode = \Plasma\Types\TypeExtensionsManager::getManager('driver-mysql')
                    ->encodeType($param, $this->paramsDef[$id]);
                
                $unsigned = $encode->isUnsigned();
                $type = $encode->getDatabaseType();
                $value = $encode->getValue();
            } catch (\Plasma\Exception $e) {
                [ $unsigned, $type, $value ] = \Plasma\Drivers\MySQL\BinaryProtocolValues::encode($param);
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
     * Whether the sequence ID should be resetted.
     * @return bool
     */
    function resetSequence(): bool {
        return true;
    }
    
    /**
     * Parses the binary resultset row and returns the row.
     * @param \Plasma\BinaryBuffer  $buffer
     * @return array
     */
    protected function parseResultsetRow(\Plasma\BinaryBuffer $buffer): array {
        $buffer->read(1); // remove packet header
        
        $nullRow = array();
        $i = 0;
        
        /** @var \Plasma\ColumnDefinitionInterface  $column */
        foreach($this->fields as $column) { // Handle NULL-bitmap
            if((\ord($buffer[(($i + 2) >> 3)]) & (1 << (($i + 2) % 8))) !== 0) {
                $nullRow[$column->getName()] = null;
            }
            
            $i++;
        }
        
        $buffer->read(((\count($this->fields) + 9) >> 3)); // Remove NULL-bitmap
        $row = array();
        
        /** @var \Plasma\ColumnDefinitionInterface  $column */
        foreach($this->fields as $column) {
            if(\array_key_exists($column->getName(), $nullRow)) {
                $row[$column->getName()] = null;
                continue;
            }
            
            try {
                $value = \Plasma\Types\TypeExtensionsManager::getManager('driver-mysql')
                    ->decodeType($column->getType(), $buffer)
                    ->getValue();
            } catch (\Plasma\Exception $e) {
                $value = \Plasma\Drivers\MySQL\BinaryProtocolValues::decode($column, $buffer);
            }
            
            $row[$column->getName()] = $value;
        }
        
        return $row;
    }
}
