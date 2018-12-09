<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL\Messages;

/**
 * Utilities for messages.
 * @internal
 */
class MessageUtility {
    /**
     * Debug.
     * @param mixed $debug
     * @return void
     * @codeCoverageIgnore
     */
    static function debug($debug): void {
        if(\getenv('PLASMA_DEBUG')) {
            echo $debug.\PHP_EOL;
            @\ob_flush();
        }
    }
}
