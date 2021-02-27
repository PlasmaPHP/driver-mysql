<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
 */

namespace Plasma\Drivers\MySQL;

use Plasma\BinaryBuffer;

/**
 * Protocol On Next object.
 * @internal
 * @codeCoverageIgnore
 */
class ProtocolOnNextCaller {
    /**
     * @var ProtocolParser
     */
    protected $parser;
    
    /**
     * @var BinaryBuffer
     */
    protected $buffer;
    
    /**
     * Constructor.
     * @param ProtocolParser  $parser
     * @param BinaryBuffer    $buffer
     */
    function __construct(ProtocolParser $parser, BinaryBuffer $buffer) {
        $this->parser = $parser;
        $this->buffer = $buffer;
    }
    
    /**
     * Get the parser.
     * @return ProtocolParser
     */
    function getParser(): ProtocolParser {
        return $this->parser;
    }
    
    /**
     * Get the buffer.
     * @return BinaryBuffer
     */
    function getBuffer(): BinaryBuffer {
        return $this->buffer;
    }
}
