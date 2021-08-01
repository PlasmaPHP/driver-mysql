<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
 */

namespace Plasma\Drivers\MySQL;

use Plasma\ColumnDefinitionInterface;

/**
 * Text protocol rowset values decoder.
 * @internal
 */
class TextProtocolValues {
    /**
     * Standard decode value, if type extensions failed.
     * @param ColumnDefinitionInterface  $column
     * @param mixed                      $param
     * @return mixed
     */
    static function decode(ColumnDefinitionInterface $column, $param) {
        $type = $column->getType();
        $flags = $column->getFlags();
        
        if($param !== null && ($flags & FieldFlags::ZEROFILL_FLAG) === 0) {
            switch(true) {
                case ($type === 'LONG'):
                    if(($flags & FieldFlags::UNSIGNED_FLAG) === 0 || \PHP_INT_SIZE > 4) {
                        $param = (int) $param;
                    }
                break;
                case ($type === 'LONGLONG'):
                    if(($flags & FieldFlags::UNSIGNED_FLAG) === 0 && \PHP_INT_SIZE > 4) {
                        $param = (int) $param;
                    }
                break;
                case static::isTypeTinyShortInt($type):
                    $param = (int) $param;
                break;
                case static::isTypeFloat($type):
                    $param = (float) $param;
                break;
                // Other types are taken as string
            }
        }
        
        return $param;
    }
    
    /**
     * @param string  $type
     * @return bool
     */
    static function isTypeTinyShortInt(string $type): bool {
        $types = array('TINY', 'SHORT', 'INT24');
        return \in_array($type, $types, true);
    }
    
    /**
     * @param string  $type
     * @return bool
     */
    static function isTypeFloat(string $type): bool {
        $types = array('FLOAT', 'DOUBLE');
        return \in_array($type, $types, true);
    }
}
