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
 * The MySQL Field Flags.
 * @internal
 */
class FieldFlags {
    const FIELD_TYPE_DECIMAL = 0x00;
    const FIELD_TYPE_TINY = 0x01;
    const FIELD_TYPE_SHORT = 0x02;
    const FIELD_TYPE_LONG = 0x03;
    const FIELD_TYPE_FLOAT = 0x04;
    const FIELD_TYPE_DOUBLE = 0x05;
    const FIELD_TYPE_NULL = 0x06;
    const FIELD_TYPE_TIMESTAMP = 0x07;
    const FIELD_TYPE_LONGLONG = 0x08;
    const FIELD_TYPE_INT24 = 0x09;
    const FIELD_TYPE_DATE = 0x0a;
    const FIELD_TYPE_TIME = 0x0b;
    const FIELD_TYPE_DATETIME = 0x0c;
    const FIELD_TYPE_YEAR = 0x0d;
    const FIELD_TYPE_NEWDATE = 0x0e;
    const FIELD_TYPE_VARCHAR = 0x0f;
    const FIELD_TYPE_BIT = 0x10;
    const FIELD_TYPE_NEWDECIMAL = 0xf6;
    const FIELD_TYPE_ENUM = 0xf7;
    const FIELD_TYPE_SET = 0xf8;
    const FIELD_TYPE_TINY_BLOB = 0xf9;
    const FIELD_TYPE_MEDIUM_BLOB = 0xfa;
    const FIELD_TYPE_LONG_BLOB = 0xfb;
    const FIELD_TYPE_BLOB = 0xfc;
    const FIELD_TYPE_VAR_STRING = 0xfd;
    const FIELD_TYPE_STRING = 0xfe;
    const FIELD_TYPE_GEOMETRY = 0xff;
    
    const NOT_NULL_FLAG = 0x1;
    const PRI_KEY_FLAG = 0x2;
    const UNIQUE_KEY_FLAG = 0x4;
    const MULTIPLE_KEY_FLAG = 0x8;
    const BLOB_FLAG = 0x10;
    const UNSIGNED_FLAG = 0x20;
    const ZEROFILL_FLAG = 0x40;
    const BINARY_FLAG = 0x80;
    const ENUM_FLAG = 0x100;
    const AUTO_INCREMENT_FLAG = 0x200;
    const TIMESTAMP_FLAG = 0x400;
    const SET_FLAG = 0x800;
}
