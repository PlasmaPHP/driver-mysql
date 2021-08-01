<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
 */

namespace Plasma\Drivers\MySQL\Messages;

use Plasma\Exception;

/**
 * Represents an exception during message parsing.
 */
class ParseException extends Exception {
    /**
     * @var int|null
     */
    protected $state;
    
    /**
     * @var string
     */
    protected $remainingBuffer;
    
    /**
     * Sets the new parser state
     * @param int  $state
     * @return void
     */
    function setState(int $state): void {
        $this->state = $state;
    }
    
    /**
     * Get the new parser state.
     * @return int|null
     */
    function getState(): ?int {
        return $this->state;
    }
    
    /**
     * Sets the remaining buffer.
     * @param string  $buffer
     * @return void
     */
    function setBuffer(string $buffer): void {
        $this->remainingBuffer = $buffer;
    }
    
    /**
     * Get the remaining buffer.
     * @return string|null
     */
    function getBuffer(): ?string {
        return $this->remainingBuffer;
    }
}
