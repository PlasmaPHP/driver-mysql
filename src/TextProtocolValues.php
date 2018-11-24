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
 * Text protocol rowset values decoder.
 * @internal
 */
class TextProtocolValues {
    /**
     * Standard decode value, if type extensions failed.
     * @param \Plasma\ColumnDefinitionInterface  $column
     * @param string|null                        $param
     * @return mixed
     * @throws \Plasma\Exception
     */
    static function decode(\Plasma\ColumnDefinitionInterface $column, $param) {
        $flags = $column->getFlags();
        
        if($param !== null && ($flags & \Plasma\Drivers\MySQL\FieldFlags::ZEROFILL_FLAG) === 0) {
            switch($column->getType()) {
                case \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_LONG:
                    if(($flags & \Plasma\Drivers\MySQL\FieldFlags::UNSIGNED_FLAG) === 0 || \PHP_INT_SIZE > 4) {
                        $param = (int) $param;
                    }
                break;
                case \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_LONGLONG:
                    if(($flags & \Plasma\Drivers\MySQL\FieldFlags::UNSIGNED_FLAG) === 0 && \PHP_INT_SIZE > 4) {
                        $param = (int) $param;
                    }
                break;
                case \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_TINY:
                case \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_SHORT:
                case \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_INT24:
                case \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_TIMESTAMP:
                    $param = (int) $param;
                break;
                case \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_FLOAT:
                case \Plasma\Drivers\MySQL\FieldFlags::FIELD_TYPE_DOUBLE:
                    $param = (float) $param;
                break;
                // Other types are taken as string
            }
        }
        
        return $param;
    }
}
