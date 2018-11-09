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
 * Protocol On Next object.
 * @internal
 */
class ProtocolOnNextCaller {
    /**
     * @var \Plasma\Drivers\MySQL\ProtocolParser
     */
    protected $parser;
    
    /**
     * @var string
     */
    protected $buffer;
    
    /**
     * Constructor.
     * @param \Plasma\Drivers\MySQL\ProtocolParser  $parser
     * @param string                                $buffer
     */
    function __construct(\Plasma\Drivers\MySQL\ProtocolParser $parser, string &$buffer) {
        $this->parser = $parser;
        $this->buffer = $buffer;
    }
    
    /**
     * Get the parser.
     * @return \Plasma\Drivers\MySQL\ProtocolParser
     */
    function getParser(): \Plasma\Drivers\MySQL\ProtocolParser {
        return $this->parser;
    }
    
    /**
     * Get the buffer.
     * @return string
     */
    function &getBuffer(): string {
        return $this->buffer;
    }
}
