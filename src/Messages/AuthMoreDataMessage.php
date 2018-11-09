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
 * Represents a Auth More Data Message.
 * @internal
 */
class AuthMoreDataMessage implements \Plasma\Drivers\MySQL\Messages\MessageInterface {
    /**
     * @var string|null
     */
    public $authPluginData;
    
    /**
     * Get the identifier for the packet.
     * @return string
     */
    static function getID(): string {
        return "\x01";
    }
    
    /**
     * Parses the message, once the complete string has been received.
     * Returns false if not enough data has been received, or the remaining buffer.
     * @param string                                $buffer
     * @param \Plasma\Drivers\MySQL\ProtocolParser  $parser
     * @return string|bool
     * @throws \Plasma\Drivers\MySQL\Messages\ParseException
     */
    function parseMessage(string $buffer, \Plasma\Drivers\MySQL\ProtocolParser $parser) {
        $this->authPluginData = $buffer;
        return '';
    }
    
    /**
     * Sets the parser state, if necessary. If not, return `-1`.
     * @return int
     */
    function setParserState(): int {
        return \Plasma\Drivers\MySQL\ProtocolParser::STATE_AUTH;
    }
}
