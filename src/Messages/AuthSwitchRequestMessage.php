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
 * Represents a Auth Switch Request Message.
 * @internal
 */
class AuthSwitchRequestMessage implements \Plasma\Drivers\MySQL\Messages\MessageInterface {
    /**
     * @var string|null
     */
    public $authPluginName;
    
    /**
     * @var string|null
     */
    public $authPluginData;
    
    /**
     * Get the identifier for the packet.
     * @return string
     */
    static function getID(): string {
        return "\xFE";
    }
    
    /**
     * Parses the message, once the complete string has been received.
     * Returns false if not enough data has been received, or the remaining buffer.
     * @return string|bool
     * @throws \Plasma\Drivers\MySQL\Messages\ParseException
     */
    function parseMessage(string $buffer) {
        $nameLength = \strpos($buffer, "\x00");
        if($nameLength === false) {
            return false;
        }
        
        if(\strlen($buffer) > $nameLength) {
            $this->authPluginName = \Plasma\Drivers\MySQL\Messages\MessageUtility::readStringNull($buffer);
            $this->authPluginData = $buffer;
        }
        
        return '';
    }
    
    /**
     * Sets the parser state, if necessary. If not, return `-1`.
     * @return int
     */
    function setParserState(): int {
        return \Plasma\Drivers\MySQL\ProtocolParser::STATE_AUTH_SENT;
    }
}
