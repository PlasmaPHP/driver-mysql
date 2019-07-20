<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL\Tests;

class ColumnDefinitionTest extends TestCase {
    function testNullable() {
        $col = new \Plasma\Drivers\MySQL\ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, \Plasma\Drivers\MySQL\FieldFlags::NOT_NULL_FLAG, null);
        $this->assertTrue($col->isNullable());
    }
    
    function testNullableNegative() {
        $col = new \Plasma\Drivers\MySQL\ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, 0, null);
        $this->assertFalse($col->isNullable());
    }
    
    function testAutoIncrement() {
        $col = new \Plasma\Drivers\MySQL\ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, \Plasma\Drivers\MySQL\FieldFlags::AUTO_INCREMENT_FLAG, null);
        $this->assertTrue($col->isAutoIncrement());
    }
    
    function testAutoIncrementNegative() {
        $col = new \Plasma\Drivers\MySQL\ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, 0, null);
        $this->assertFalse($col->isAutoIncrement());
    }
    
    function testPrimaryKey() {
        $col = new \Plasma\Drivers\MySQL\ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, \Plasma\Drivers\MySQL\FieldFlags::PRI_KEY_FLAG, null);
        $this->assertTrue($col->isPrimaryKey());
    }
    
    function testPrimaryKeyNegative() {
        $col = new \Plasma\Drivers\MySQL\ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, 0, null);
        $this->assertFalse($col->isPrimaryKey());
    }
    
    function testUniqueKey() {
        $col = new \Plasma\Drivers\MySQL\ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, \Plasma\Drivers\MySQL\FieldFlags::UNIQUE_KEY_FLAG, null);
        $this->assertTrue($col->isUniqueKey());
    }
    
    function testUniqueKeyNegative() {
        $col = new \Plasma\Drivers\MySQL\ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, 0, null);
        $this->assertFalse($col->isUniqueKey());
    }
    
    function testMultipleKey() {
        $col = new \Plasma\Drivers\MySQL\ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, \Plasma\Drivers\MySQL\FieldFlags::MULTIPLE_KEY_FLAG, null);
        $this->assertTrue($col->isMultipleKey());
    }
    
    function testMultipleKeyNegative() {
        $col = new \Plasma\Drivers\MySQL\ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, 0, null);
        $this->assertFalse($col->isMultipleKey());
    }
    
    function testUnsigned() {
        $col = new \Plasma\Drivers\MySQL\ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, \Plasma\Drivers\MySQL\FieldFlags::UNSIGNED_FLAG, null);
        $this->assertTrue($col->isUnsigned());
    }
    
    function testUnsignedNegative() {
        $col = new \Plasma\Drivers\MySQL\ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, 0, null);
        $this->assertFalse($col->isUnsigned());
    }
    
    function testZerofilled() {
        $col = new \Plasma\Drivers\MySQL\ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, \Plasma\Drivers\MySQL\FieldFlags::ZEROFILL_FLAG, null);
        $this->assertTrue($col->isZerofilled());
    }
    
    function testZerofilledNegative() {
        $col = new \Plasma\Drivers\MySQL\ColumnDefinition('test2', 'test5', 'VARCHAR', 'utf8mb4', 50, 0, null);
        $this->assertFalse($col->isZerofilled());
    }
}
