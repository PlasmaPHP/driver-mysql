<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL;

/**
 * Binary protocol rowset values decoder and encoder.
 * @internal
 */
class BinaryProtocolValues {
    /**
     * Standard encode value, if type extensions failed.
     * @param mixed  $param
     * @return array
     * @throws \Plasma\Exception
     */
    static function encode($param): array {
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
    static function decode(\Plasma\ColumnDefinitionInterface $column, \Plasma\BinaryBuffer $buffer) {
        $flags = $column->getFlags();
        $type = $column->getType();
        
        switch(true) {
            case static::isTypeString($type):
                $value = $buffer->readStringLength();
            break;
            case ($type === 'TINY'):
                $value = $buffer->readInt1();
                $value = static::zeroFillInts($column, $value);
            break;
            case static::isTypeShortOrYear($type):
                $value = $buffer->readInt2();
                $value = static::zeroFillInts($column, $value);
            break;
            case static::isTypeInt24orLong($type):
                $value = $buffer->readInt4();
                
                if(($flags & \Plasma\Drivers\MySQL\FieldFlags::UNSIGNED_FLAG) !== 0 && \PHP_INT_SIZE <= 4) {
                    $value = \bcadd($value, '18446744073709551616');
                }
                
                $value = static::zeroFillInts($column, $value);
            break;
            case ($type === 'LONGLONG'):
                $value = $buffer->readInt8();
                
                if(($flags & \Plasma\Drivers\MySQL\FieldFlags::UNSIGNED_FLAG) !== 0) {
                    $value = \bcadd($value, '18446744073709551616');
                } elseif(\PHP_INT_SIZE > 4) {
                    $value = (int) $value;
                }
                
                $value = static::zeroFillInts($column, $value);
            break;
            case ($type === 'FLOAT'):
                $value = $buffer->readFloat();
            break;
            case ($type === 'DOUBLE'):
                $value = $buffer->readDouble();
            break;
            case static::isTypeDate($type):
                $length = $buffer->readIntLength();
                if($length > 0) {
                    $year = $buffer->readInt2();
                    $month = $buffer->readInt1();
                    $day = $buffer->readInt1();
                    
                    $value = \sprintf('%04d-%02d-%02d', $year, $month, $day);
                } else {
                    $value = '0000-00-00';
                }
            break;
            case static::isTypeDateTime($type):
                $value = static::parseDateTime($type, $buffer);
            break;
            case ($type === 'TIME'):
                $value = static::parseTime($buffer);
            break;
            default:
                throw new \InvalidArgumentException('Unknown column type (flags: '.$flags.', type: '.$type.')');
            break;
        }
        
        return $value;
    }
    
    /**
     * @param string  $type
     * @return bool
     */
    static function isTypeString(string $type): bool {
        $types = array(
            'STRING', 'VARCHAR', 'VARSTRING', 'ENUM', 'SET', 'LONGBLOB',
            'MEDIUMBLOB', 'BLOB', 'TINYBLOB', 'GEMOTERY', 'BIT', 'DECIMAL',
            'NEWDECIMAL', 'JSON'
        );
        
        return \in_array($type, $types, true);
    }
    
    /**
     * @param string  $type
     * @return bool
     */
    static function isTypeShortOrYear(string $type): bool {
        $types = array('SHORT', 'YEAR');
        return \in_array($type, $types, true);
    }
    
    /**
     * @param string  $type
     * @return bool
     */
    static function isTypeInt24orLong(string $type): bool {
        $types = array('INT24', 'LONG');
        return \in_array($type, $types, true);
    }
    
    /**
     * @param string  $type
     * @return bool
     */
    static function isTypeDate(string $type): bool {
        $types = array('DATE', 'NEWDATE');
        return \in_array($type, $types, true);
    }
    
    /**
     * @param string  $type
     * @return bool
     */
    static function isTypeDateTime(string $type): bool {
        $types = array('DATETIME', 'TIMESTAMP');
        return \in_array($type, $types, true);
    }
    
    /**
     * Parses a DATETIME or TIMESTAMP value.
     * @param string                $type
     * @param \Plasma\BinaryBuffer  $buffer
     * @return mixed
     */
    static function parseDateTime(string $type, \Plasma\BinaryBuffer $buffer) {
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
                $microI = $buffer->readInt4();
            } else {
                $microI = 0;
            }
            
            $micro = \str_pad($microI, 6, '0', \STR_PAD_LEFT);
            $micro = \substr($micro, 0, 3).' '.\substr($micro, 3);
            
            $value = \sprintf('%04d-%02d-%02d %02d:%02d:%02d'.($microI > 0 ? '.%s' : ''), $year, $month, $day, $hour, $min, $sec, $micro);
            
            if($type === 'TIMESTAMP') {
                $value = \DateTime::createFromFormat('Y-m-d H:i:s'.($microI > 0 ? '.u' : ''), $value)->getTimestamp();
            }
        } else {
            $value = '0000-00-00 00:00:00.000 000';
        }
        
        return $value;
    }
    
    /**
     * Parses a TIME value.
     * @param \Plasma\BinaryBuffer  $buffer
     * @return mixed
     */
    static function parseTime(\Plasma\BinaryBuffer $buffer) {
        $length = $buffer->readIntLength();
        if($length > 1) {
            $sign = $buffer->readInt1();
            $days = $buffer->readInt4();
            
            if($sign === 1) {
                $days *= -1;
            }
            
            $hour = $buffer->readInt1();
            $min = $buffer->readInt1();
            $sec = $buffer->readInt1();
            
            if($length > 8) {
                $microI = $buffer->readInt4();
            } else {
                $microI = 0;
            }
            
            $micro = \str_pad($microI, 6, '0', \STR_PAD_LEFT);
            $micro = \substr($micro, 0, 3).' '.\substr($micro, 3);
            
            $value = \sprintf('%dd %02d:%02d:%02d'.($microI > 0 ? '.%s' : ''), $days, $hour, $min, $sec, $micro);
        } else {
            $value = '0d 00:00:00';
        }
        
        return $value;
    }
    
    /**
     * @param \Plasma\ColumnDefinitionInterface  $column
     * @param string|int                         $value
     * @return string|int
     */
    static function zeroFillInts(\Plasma\ColumnDefinitionInterface $column, $value) {
        $flags = $column->getFlags();
        
        if(($flags & \Plasma\Drivers\MySQL\FieldFlags::ZEROFILL_FLAG) !== 0) {
            $value = \str_pad(((string) $value), $column->getLength(), '0', \STR_PAD_LEFT);
        }
        
        return $value;
    }
}
