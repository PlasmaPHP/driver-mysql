<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL;

/**
 * The MySQL Field Flags.
 */
class FieldFlags {
    /**
     * Decimal.
     * @var int
     * @source
     */
    const FIELD_TYPE_DECIMAL = 0x00;
    
    /**
     * Tiny int. 1 byte.
     * @var int
     * @source
     */
    const FIELD_TYPE_TINY = 0x01;
    
    /**
     * Short int. 2 bytes.
     * @var int
     * @source
     */
    const FIELD_TYPE_SHORT = 0x02;
    
    /**
     * Integer. 4 bytes.
     * @var int
     * @source
     */
    const FIELD_TYPE_LONG = 0x03;
    
    /**
     * Float. 4 bytes.
     * @var int
     * @source
     */
    const FIELD_TYPE_FLOAT = 0x04;
    
    /**
     * Double. 8 bytes.
     * @var int
     * @source
     */
    const FIELD_TYPE_DOUBLE = 0x05;
    
    /**
     * Null.
     * @var int
     * @source
     */
    const FIELD_TYPE_NULL = 0x06;
    
    /**
     * Timestamp.
     * @var int
     * @source
     */
    const FIELD_TYPE_TIMESTAMP = 0x07;
    
    /**
     * Integer. 8 bytes.
     * @var int
     * @source
     */
    const FIELD_TYPE_LONGLONG = 0x08;
    
    /**
     * Integer. 24 bits.
     * @var int
     * @source
     */
    const FIELD_TYPE_INT24 = 0x09;
    
    /**
     * Date.
     * @var int
     * @source
     */
    const FIELD_TYPE_DATE = 0x0A;
    
    /**
     * Time.
     * @var int
     * @source
     */
    const FIELD_TYPE_TIME = 0x0B;
    
    /**
     * Datetime.
     * @var int
     * @source
     */
    const FIELD_TYPE_DATETIME = 0x0C;
    
    /**
     * Year.
     * @var int
     * @source
     */
    const FIELD_TYPE_YEAR = 0x0D;
    
    /**
     * New date.
     * @var int
     * @source
     */
    const FIELD_TYPE_NEWDATE = 0x0E;
    
    /**
     * Varchar.
     * @var int
     * @source
     */
    const FIELD_TYPE_VARCHAR = 0x0F;
    
    /**
     * Bit.
     * @var int
     * @source
     */
    const FIELD_TYPE_BIT = 0x10;
    
    /**
     * JSON.
     * @var int
     * @source
     */
    const FIELD_TYPE_JSON = 0xF5;
    
    /**
     * Newdecimal.
     * @var int
     * @source
     */
    const FIELD_TYPE_NEWDECIMAL = 0xF6;
    
    /**
     * Enum.
     * @var int
     * @source
     */
    const FIELD_TYPE_ENUM = 0xF7;
    
    /**
     * Set.
     * @var int
     * @source
     */
    const FIELD_TYPE_SET = 0xF8;
    
    /**
     * Tiny blob. Binary.
     * @var int
     * @source
     */
    const FIELD_TYPE_TINY_BLOB = 0xF9;
    
    /**
     * Medium blob. Binary.
     * @var int
     * @source
     */
    const FIELD_TYPE_MEDIUM_BLOB = 0xFA;
    
    /**
     * Long blob. Binary.
     * @var int
     * @source
     */
    const FIELD_TYPE_LONG_BLOB = 0xFB;
    
    /**
     * Blob. Not the google ones. Binary.
     * @var int
     * @source
     */
    const FIELD_TYPE_BLOB = 0xFC;
    
    /**
     * Variable string.
     * @var int
     * @source
     */
    const FIELD_TYPE_VAR_STRING = 0xFD;
    
    /**
     * String.
     * @var int
     * @source
     */
    const FIELD_TYPE_STRING = 0xFE;
    
    /**
     * Geometry. You know, some sort of witchcraft.
     * @var int
     * @source
     */
    const FIELD_TYPE_GEOMETRY = 0xFF;
    
    /**
     * Whether the column can not be `NULL`.
     * @var int
     * @source
     */
    const NOT_NULL_FLAG = 0x1;
    
    /**
     * Whether the column is a primary key.
     * @var int
     * @source
     */
    const PRI_KEY_FLAG = 0x2;
    
    /**
     * Whether the column must be unique.
     * @var int
     * @source
     */
    const UNIQUE_KEY_FLAG = 0x4;
    
    /**
     * Whether the column is part of a key.
     * @var int
     * @source
     */
    const MULTIPLE_KEY_FLAG = 0x8;
    
    /**
     * Whether the column is a blob. Still not the google ones.
     * @var int
     * @source
     */
    const BLOB_FLAG = 0x10;
    
    /**
     * Whether the column is unsigned.
     * @var int
     * @source
     */
    const UNSIGNED_FLAG = 0x20;
    
    /**
     * Whether the column will be filled with zeroes to the column length.
     * @var int
     * @source
     */
    const ZEROFILL_FLAG = 0x40;
    
    /**
     * Whether the column is binary.
     * @var int
     * @source
     */
    const BINARY_FLAG = 0x80;
    
    /**
     * Whether the column is an enum.
     * @var int
     * @source
     */
    const ENUM_FLAG = 0x100;
    
    /**
     * Whether the column automatically increments.
     * @var int
     * @source
     */
    const AUTO_INCREMENT_FLAG = 0x200;
    
    /**
     * Whether the column is a timestamp.
     * @var int
     * @source
     */
    const TIMESTAMP_FLAG = 0x400;
    
    /**
     * Whether the column is a set.
     * @var int
     * @source
     */
    const SET_FLAG = 0x800;
    
    /**
     * Maps the int to the type name.
     * @var string[]
     * @source
     */
    const TYPE_MAP = array(
        FieldFlags::FIELD_TYPE_DECIMAL => 'DECIMAL',
        FieldFlags::FIELD_TYPE_TINY => 'TINY',
        FieldFlags::FIELD_TYPE_SHORT => 'SHORT',
        FieldFlags::FIELD_TYPE_LONG => 'LONG',
        FieldFlags::FIELD_TYPE_FLOAT => 'FLOAT',
        FieldFlags::FIELD_TYPE_DOUBLE => 'DOUBLE',
        FieldFlags::FIELD_TYPE_NULL => 'NULL',
        FieldFlags::FIELD_TYPE_TIMESTAMP => 'TIMESTAMP',
        FieldFlags::FIELD_TYPE_LONGLONG => 'LONGLONG',
        FieldFlags::FIELD_TYPE_INT24 => 'INT24',
        FieldFlags::FIELD_TYPE_DATE => 'DATE',
        FieldFlags::FIELD_TYPE_TIME => 'TIME',
        FieldFlags::FIELD_TYPE_DATETIME => 'DATETIME',
        FieldFlags::FIELD_TYPE_YEAR => 'YEAR',
        FieldFlags::FIELD_TYPE_NEWDATE => 'NEWDATE',
        FieldFlags::FIELD_TYPE_VARCHAR => 'VARCHAR',
        FieldFlags::FIELD_TYPE_BIT => 'BIT',
        FieldFlags::FIELD_TYPE_JSON => 'JSON',
        FieldFlags::FIELD_TYPE_NEWDECIMAL => 'NEWDECIMAL',
        FieldFlags::FIELD_TYPE_ENUM => 'ENUM',
        FieldFlags::FIELD_TYPE_SET => 'SET',
        FieldFlags::FIELD_TYPE_TINY_BLOB => 'TINYBLOB',
        FieldFlags::FIELD_TYPE_MEDIUM_BLOB => 'MEDIUMBLOB',
        FieldFlags::FIELD_TYPE_LONG_BLOB => 'LONGBLOB',
        FieldFlags::FIELD_TYPE_BLOB => 'BLOB',
        FieldFlags::FIELD_TYPE_VAR_STRING => 'VARSTRING',
        FieldFlags::FIELD_TYPE_STRING => 'STRING',
        FieldFlags::FIELD_TYPE_GEOMETRY => 'GEOMETRY'
    );
}
