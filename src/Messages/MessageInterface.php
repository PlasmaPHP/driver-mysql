<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL\Messages;

/**
 * Represents an incoming message.
 */
interface MessageInterface {
    /**
     * Constructor.
     * @param \Plasma\Drivers\MySQL\ProtocolParser  $parser
     */
    function __construct(\Plasma\Drivers\MySQL\ProtocolParser $parser);
    
    /**
     * Get the identifier for the packet.
     * @return string
     */
    static function getID(): string;
    
    /**
     * Parses the message, once the complete string has been received.
     * Return false if not enough data has been received.
     * @param \Plasma\BinaryBuffer  $buffer
     * @return bool
     * @throws \Plasma\Drivers\MySQL\Messages\ParseException
     */
    function parseMessage(\Plasma\BinaryBuffer $buffer): bool;
    
    /**
     * Get the parser which created this message.
     * @return \Plasma\Drivers\MySQL\ProtocolParser
     */
    function getParser(): \Plasma\Drivers\MySQL\ProtocolParser;
    
    /**
     * Sets the parser state, if necessary. If not, return `-1`.
     * @return int
     */
    function setParserState(): int;
}
