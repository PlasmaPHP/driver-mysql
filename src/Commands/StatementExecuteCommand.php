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
     * Constructor.
     * @param \Plasma\DriverInterface  $driver
     * @param mixed                    $id
     * @param string                   $query
     * @param array                    $params
     */
    function __construct(\Plasma\DriverInterface $driver, $id, string $query, array $params) {
        parent::__construct($driver, $query);
        
        $this->id = $id;
        $this->params = $params;
    }
    
    /**
     * Get the encoded message for writing to the database connection.
     * @return string
     */
    function getEncodedMessage(): string {
        $packet = \chr(static::COMMAND_ID);
        $packet .= \Plasma\Drivers\MySQL\Messages\MessageUtility::writeInt4($this->id);
        
        $packet .= "\0"; // Cursor type flag
        $packet .= \Plasma\Drivers\MySQL\Messages\MessageUtility::writeInt4(1); // Iteration count is always 1
        
        $paramCount = \count($this->params);
        
        $bitmapOffset = \strlen($payload);
        $packet .= \str_repeat("\0", (($paramCount + 7) >> 3));
        
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
            
            $unsigned = false;
            
            try {
                $manager = \Plasma\Types\TypeExtensionsManager::getManager('driver-mysql');
                $encode = $manager->encodeType($param);
                
                $unsigned = $encode->isUnsigned();
                $type = $encode->getSQLType();
                $value = $encode->getValue();
            } catch (\Plasma\Exception $e) {
                [ $unsigned, $type, $value ] = $this->stdEncodeValue($param);
            }
            
            $types .= \chr($type);
            $types .= ($unsigned ? "\x80" : "\0");
            
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
     * Parses the binary resultset row and returns the row.
     * @param \Plasma\ColumnDefinitionInterface  $column
     * @param string                             $buffer
     * @return array
     */
    protected function parseResultsetRow(string &$buffer): array {
        $buffer = \substr($buffer, 1); // Skip first byte (header)
        
        $nullRow = array();
        $i = 0;
        
        /** @var \Plasma\ColumnDefinitionInterface  $column */
        foreach($this->fields as $column) { // Handle NULL-bitmap
            if((\ord($buffer[(($i + 2) >> 3)]) & (1 << (($i + 2) % 8))) !== 0) {
                $nullRow[$column->getName()] = null;
            }
            
            $i++;
        }
        
        $buffer = \substr($buffer, ((\count($column) + 9) >> 3)); // Remove NULL-bitmap
        $row = array();
        
        /** @var \Plasma\ColumnDefinitionInterface  $column */
        foreach($this->fields as $column) {
            if(\array_key_exists($columm->getName(), $nullRow)) {
                $row[$columm->getName()] = $nullRow[$columm->getName()];
                continue;
            }
            
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
     * Standard encode value, if type extensions failed.
     * @param mixed  $param
     * @return array
     * @throws \Plasma\Exception
     */
    protected function stdEncodeValue($param): array {
        $unsigned = false;
        
        switch(\gettype($param)) {
            case 'boolean':
                $type = \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_TINY;
                $value = ($param ? "\x01" : "\0");
            break;
            case 'integer':
                if($param >= 0) {
                    $unsigned = true;
                }
                
                // TODO: Check if short, long or long long
                if($param >= 0 && $param < (1 << 15)) {
                    $type = \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_SHORT;
                    $value = \Plasma\Drivers\MySQL\Messages\MessageUtility::writeInt2($param);
                } elseif(\PHP_INT_SIZE === 4) {
                    $type = \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_LONG;
                    $value = \Plasma\Drivers\MySQL\Messages\MessageUtility::writeInt4($param);
                } else {
                    $type = \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_LONGLONG;
                    $value = \Plasma\Drivers\MySQL\Messages\MessageUtility::writeInt8($param);
                }
            break;
            case 'double':
                $type = \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_DOUBLE;
                $value = \Plasma\Drivers\MySQL\Messages\MessageUtility::writeFloat($param);
            break;
            case 'string':
                $type = \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_LONG_BLOB;
                
                $value = \Plasma\Drivers\MySQL\Messages\MessageUtility::writeInt4(\strlen($param));
                $value .= $param;
            break;
            case 'NULL':
                $type = \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_NULL;
                $value = '';
            break;
            default:
                throw new \Plasma\Exception('Unexpected type for binding parameter: '.\gettype($param));
            break;
        }
        
        return array($unsigned, $type, $value);
    }
    
    /**
     * Whether the sequence ID should be resetted.
     * @return bool
     */
    function resetSequence(): bool {
        return false;
    }
}
