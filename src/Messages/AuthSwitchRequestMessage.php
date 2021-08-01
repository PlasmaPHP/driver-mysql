<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
 */

namespace Plasma\Drivers\MySQL\Messages;

use Plasma\BinaryBuffer;
use Plasma\Drivers\MySQL\ProtocolParser;

/**
 * Represents an Auth Switch Request Message.
 */
class AuthSwitchRequestMessage implements MessageInterface {
    /**
     * @var string|null
     */
    public $authPluginName;
    
    /**
     * @var string|null
     */
    public $authPluginData;
    
    /**
     * @var ProtocolParser
     * @internal
     */
    protected $parser;
    
    /**
     * Constructor.
     * @param ProtocolParser  $parser
     * @internal
     */
    function __construct(ProtocolParser $parser) {
        $this->parser = $parser;
    }
    
    /**
     * Get the identifier for the packet.
     * @return string
     * @internal
     */
    static function getID(): string {
        return "\xFE";
    }
    
    /**
     * Parses the message, once the complete string has been received.
     * Returns false if not enough data has been received, or the remaining buffer.
     * @param BinaryBuffer  $buffer
     * @return bool
     * @internal
     */
    function parseMessage(BinaryBuffer $buffer): bool {
        try {
            $this->authPluginName = $buffer->readStringNull();
            
            if($buffer->getSize() > 0) {
                $this->authPluginData = $buffer->getContents();
                $buffer->clear();
            }
            
            return true;
        } catch (\OverflowException $e) {
            return false;
        }
    }
    
    /**
     * Get the parser which created this message.
     * @return ProtocolParser
     * @internal
     */
    function getParser(): ProtocolParser {
        return $this->parser;
    }
    
    /**
     * Sets the parser state, if necessary. If not, return `-1`.
     * @return int
     * @internal
     */
    function setParserState(): int {
        return ProtocolParser::STATE_AUTH_SENT;
    }
}
