<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
 * @noinspection PhpUnhandledExceptionInspection
*/

namespace Plasma\Drivers\MySQL\Tests;

use Plasma\Drivers\MySQL\ColumnDefinition;
use Plasma\Drivers\MySQL\FieldFlags;

class ColumnDefinitionTest extends TestCase {
    function testNullable() {
        $col = new ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, FieldFlags::NOT_NULL_FLAG, null);
        self::assertTrue($col->isNullable());
    }
    
    function testNullableNegative() {
        $col = new ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, 0, null);
        self::assertFalse($col->isNullable());
    }
    
    function testAutoIncrement() {
        $col = new ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, FieldFlags::AUTO_INCREMENT_FLAG, null);
        self::assertTrue($col->isAutoIncrement());
    }
    
    function testAutoIncrementNegative() {
        $col = new ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, 0, null);
        self::assertFalse($col->isAutoIncrement());
    }
    
    function testPrimaryKey() {
        $col = new ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, FieldFlags::PRI_KEY_FLAG, null);
        self::assertTrue($col->isPrimaryKey());
    }
    
    function testPrimaryKeyNegative() {
        $col = new ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, 0, null);
        self::assertFalse($col->isPrimaryKey());
    }
    
    function testUniqueKey() {
        $col = new ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, FieldFlags::UNIQUE_KEY_FLAG, null);
        self::assertTrue($col->isUniqueKey());
    }
    
    function testUniqueKeyNegative() {
        $col = new ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, 0, null);
        self::assertFalse($col->isUniqueKey());
    }
    
    function testMultipleKey() {
        $col = new ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, FieldFlags::MULTIPLE_KEY_FLAG, null);
        self::assertTrue($col->isMultipleKey());
    }
    
    function testMultipleKeyNegative() {
        $col = new ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, 0, null);
        self::assertFalse($col->isMultipleKey());
    }
    
    function testUnsigned() {
        $col = new ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, FieldFlags::UNSIGNED_FLAG, null);
        self::assertTrue($col->isUnsigned());
    }
    
    function testUnsignedNegative() {
        $col = new ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, 0, null);
        self::assertFalse($col->isUnsigned());
    }
    
    function testZerofilled() {
        $col = new ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, FieldFlags::ZEROFILL_FLAG, null);
        self::assertTrue($col->isZerofilled());
    }
    
    function testZerofilledNegative() {
        $col = new ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, 0, null);
        self::assertFalse($col->isZerofilled());
    }
}
