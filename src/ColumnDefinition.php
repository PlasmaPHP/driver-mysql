<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
 */

namespace Plasma\Drivers\MySQL;

use Plasma\AbstractColumnDefinition;

/**
 * Column Definitions define columns (who would've thought of that?). Such as their name, type, length, etc.
 */
class ColumnDefinition extends AbstractColumnDefinition {
    /**
     * Whether the column is nullable (not `NOT NULL`).
     * @return bool
     */
    function isNullable(): bool {
        return (($this->flags & FieldFlags::NOT_NULL_FLAG) !== 0);
    }
    
    /**
     * Whether the column is auto incremented.
     * @return bool
     */
    function isAutoIncrement(): bool {
        return (($this->flags & FieldFlags::AUTO_INCREMENT_FLAG) !== 0);
    }
    
    /**
     * Whether the column is the primary key.
     * @return bool
     */
    function isPrimaryKey(): bool {
        return (($this->flags & FieldFlags::PRI_KEY_FLAG) !== 0);
    }
    
    /**
     * Whether the column is the unique key.
     * @return bool
     */
    function isUniqueKey(): bool {
        return (($this->flags & FieldFlags::UNIQUE_KEY_FLAG) !== 0);
    }
    
    /**
     * Whether the column is part of a multiple/composite key.
     * @return bool
     */
    function isMultipleKey(): bool {
        return (($this->flags & FieldFlags::MULTIPLE_KEY_FLAG) !== 0);
    }
    
    /**
     * Whether the column is unsigned (only makes sense for numeric types).
     * @return bool
     */
    function isUnsigned(): bool {
        return (($this->flags & FieldFlags::UNSIGNED_FLAG) !== 0);
    }
    
    /**
     * Whether the column gets zerofilled to the length.
     * @return bool
     */
    function isZerofilled(): bool {
        return (($this->flags & FieldFlags::ZEROFILL_FLAG) !== 0);
    }
}
