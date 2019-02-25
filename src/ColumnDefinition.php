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
 * Column Definitions define columns (who would've thought of that?). Such as their name, type, length, etc.
 */
class ColumnDefinition extends \Plasma\ColumnDefinition {
    /**
     * Whether the column is nullable (not `NOT NULL`).
     * @return bool
     */
    function isNullable(): bool {
        return (($this->flags & \Plasma\Drivers\MySQL\FieldFlags::NOT_NULL_FLAG) !== 0);
    }
    
    /**
     * Whether the column is auto incremented.
     * @return bool
     */
    function isAutoIncrement(): bool {
        return (($this->flags & \Plasma\Drivers\MySQL\FieldFlags::AUTO_INCREMENT_FLAG) !== 0);
    }
    
    /**
     * Whether the column is the primary key.
     * @return bool
     */
    function isPrimaryKey(): bool {
        return (($this->flags & \Plasma\Drivers\MySQL\FieldFlags::PRI_KEY_FLAG) !== 0);
    }
    
    /**
     * Whether the column is the unique key.
     * @return bool
     */
    function isUniqueKey(): bool {
        return (($this->flags & \Plasma\Drivers\MySQL\FieldFlags::UNIQUE_KEY_FLAG) !== 0);
    }
    
    /**
     * Whether the column is part of a multiple/composite key.
     * @return bool
     */
    function isMultipleKey(): bool {
        return (($this->flags & \Plasma\Drivers\MySQL\FieldFlags::MULTIPLE_KEY_FLAG) !== 0);
    }
    
    /**
     * Whether the column is unsigned (only makes sense for numeric types).
     * @return bool
     */
    function isUnsigned(): bool {
        return (($this->flags & \Plasma\Drivers\MySQL\FieldFlags::UNSIGNED_FLAG) !== 0);
    }
    
    /**
     * Whether the column gets zerofilled to the length.
     * @return bool
     */
    function isZerofilled(): bool {
        return (($this->flags & \Plasma\Drivers\MySQL\FieldFlags::ZEROFILL_FLAG) !== 0);
    }
}
