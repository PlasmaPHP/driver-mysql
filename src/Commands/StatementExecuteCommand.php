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
                $manager = \Plasma\Types\TypeExtensionsManager::getManager('driver-mysql');
                $encode = $manager->encodeType($param);
                
                $unsigned = $encode->isUnsigned();
                $type = $encode->getSQLType();
                $value = $encode->getValue();
            } catch (\Plasma\Exception $e) {
                [ $unsigned, $type, $value ] = $this->stdEncodeValue($param);
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
            
            $value = $this->stdDecodeBinaryValue($column, $buffer);
            $parsedValue = $column->parseValue($value);
            
            if($value !== $parsedValue) {
                $value = $parsedValue;
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
                
                if($param >= 0 && $param < (1 << 15)) {
                    $type = \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_SHORT;
                    $value = \Plasma\BinaryBuffer::writeInt2($param);
                } elseif(\PHP_INT_SIZE === 4) {
                    $type = \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_LONG;
                    $value = \Plasma\BinaryBuffer::writeInt4($param);
                } else {
                    $type = \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_LONGLONG;
                    $value = \Plasma\BinaryBuffer::writeInt8($param);
                }
            break;
            case 'double':
                $type = \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_DOUBLE;
                $value = \Plasma\BinaryBuffer::writeDouble($param);
            break;
            case 'string':
                $type = \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_LONG_BLOB;
                $value = \Plasma\BinaryBuffer::writeStringLength($param);
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
     * Standard decode value, if type extensions failed.
     * @param \Plasma\ColumnDefinitionInterface  $column
     * @param \Plasma\BinaryBuffer               $buffer
     * @return mixed
     * @throws \Plasma\Exception
     */
    protected function stdDecodeBinaryValue(\Plasma\ColumnDefinitionInterface $column, \Plasma\BinaryBuffer $buffer) {
        $flags = $column->getFlags();
        
        switch(true) {
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_STRING) !== 0):
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_VARCHAR) !== 0):
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_VAR_STRING) !== 0):
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_ENUM) !== 0):
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_SET) !== 0):
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_LONG_BLOB) !== 0):
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_MEDIUM_BLOB) !== 0):
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_BLOB) !== 0):
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_TINY_BLOB) !== 0):
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_GEOMETRY) !== 0):
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_BIT) !== 0):
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_DECIMAL) !== 0):
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_NEWDECIMAL) !== 0):
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_JSON) !== 0):
            case ($column->getType() === 'VARSTRING'): // WTF is going on ?! flags = 0, type = VARSTRING
                $value = $buffer->readStringLength();
            break;
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_TINY) !== 0):
                $value = $buffer->readInt1();
                $value = $this->zeroFillInts($column, $value);
            break;
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_SHORT) !== 0):
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_YEAR) !== 0):
                $value = $buffer->readInt2();
                $value = $this->zeroFillInts($column, $value);
            break;
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_INT24) !== 0):
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_LONG) !== 0):
                $value = $buffer->readInt4();
            
                if(($flags & \Plasma\Drivers\MySQL\FieldFlags::UNSIGNED_FLAG) !== 0 && \PHP_INT_SIZE <= 4) {
                    $value = \bcadd($value, '18446744073709551616');
                }
                
                $value = $this->zeroFillInts($column, $value);
            break;
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_LONGLONG) !== 0):
                $value = $buffer->readInt8();
                
                if(($flags & \Plasma\Drivers\MySQL\FieldFlags::UNSIGNED_FLAG) !== 0) {
                    $value = \bcadd($value, '18446744073709551616');
                } elseif(\PHP_INT_SIZE > 4) {
                    $value = (int) $value;
                }
                
                $value = $this->zeroFillInts($column, $value);
            break;
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_FLOAT) !== 0):
                $value = $buffer->readFloat();
            break;
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_DOUBLE) !== 0):
                $value = $buffer->readDouble();
            break;
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_DATE) !== 0):
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_NEWDATE) !== 0):
                $length = $buffer->readIntLength();
                if($length > 0) {
                    $year = $buffer->readInt2();
                    $month = $buffer->readInt1();
                    $day = $buffer->readInt1();
                    
                    $value = \sprintf('%d-%d-%d', $year, $month, $day);
                } else {
                    $value = '0000-00-00';
                }
            break;
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_DATETIME) !== 0):
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_TIMESTAMP) !== 0):
                $length = $buffer->readIntLength();
                if($length > 0) {
                    $year = $buffer->readInt2();
                    $month = $buffer->readInt1();
                    $day = $buffer->readInt1();
                    
                    if($length > 4) {
                        $hour = $buffer->readInt1();
                        $min = $buffer->readInt1();
                        $sec = $buffer->readInt1();
                    } else {
                        $hour = 0;
                        $min = 0;
                        $sec = 0;
                    }
                    
                    if($length > 7) {
                        $micro = $buffer->readInt4();
                    } else {
                        $micro = 0;
                    }
                    
                    $timestamp = (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_TIMESTAMP) !== 0);
                    
                    $micro = \str_pad($micro, 6, '0', \STR_PAD_LEFT);
                    $micro = ($timestamp ? $micro : \substr($micro, 0, 3).' '.\substr($micro, 3));
                    
                    $value = \sprintf('%d-%d-%d %d:%d:%d'.($micro > 0 ? '.%s' : ''), $year, $month, $day, $hour, $min, $sec, $micro);
                    
                    if($timestamp) {
                        $value = \DateTime::createFromFormat('Y-m-d H:i:s'.($micro > 0 ? '.u' : ''), $value)->getTimestamp();
                    }
                } else {
                    $value = '0000-00-00 00:00:00.000 000';
                }
            break;
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_TIME) !== 0):
                $length = $buffer->readIntLength();
                if($length > 0) {
                    $sign = $buffer->readInt1();
                    $days = $buffer->readInt4();
                    
                    if($sign === 1) {
                        $days *= -1;
                    }
                    
                    $hour = $buffer->readInt1();
                    $min = $buffer->readInt1();
                    $sec = $buffer->readInt1();
                    
                    if($length > 8) {
                        $micro = $buffer->readInt4();
                    } else {
                        $micro = 0;
                    }
                    
                    $micro = \str_pad($micro, 6, '0', \STR_PAD_LEFT);
                    $micro = \substr($micro, 0, 3).' '.\substr($micro, 3);
                    
                    $value = \sprintf('%dd %d:%d:%d'.($micro > 0 ? '.%s' : ''), $days, $hour, $min, $sec, $micro);
                } else {
                    $value = '0d 00:00:00';
                }
            break;
            case (($flags & \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_NULL) !== 0):
                $value = null;
            break;
            default:
                throw new \InvalidArgumentException('Unknown column type (flags: '.$flags.', type: '.$column->getType().')');
            break;
        }
        
        return $value;
    }
    
    /**
     * @param \Plasma\ColumnDefinitionInterface  $column
     * @param string|int                         $value
     * @return string|int
     */
    protected function zeroFillInts(\Plasma\ColumnDefinitionInterface $column, $value) {
        $flags = $column->getFlags();
        
        if(($flags & \Plasma\Drivers\MySQL\FieldFlags::ZEROFILL_FLAG) !== 0) {
            $value = \str_pad(((string) $value), $column->getLength(), '0', \STR_PAD_LEFT);
        }
        
        return $value;
    }
}
