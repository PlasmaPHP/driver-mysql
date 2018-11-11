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
 * Represents an EOF Message.
 * @internal
 */
class EOFMessage implements \Plasma\Drivers\MySQL\Messages\MessageInterface {
    /**
     * The server status flags.
     * @var int
     * @see \Plasma\Drivers\MySQL\StatusFlags
     */
    public $statusFlags;
    
    /**
     * Count of warnings.
     * @var int
     */
    public $warningsCount;
    
    /**
     * @var \Plasma\Drivers\MySQL\ProtocolParser
     */
    protected $parser;
    
    /**
     * Constructor.
     * @param \Plasma\Drivers\MySQL\ProtocolParser  $parser
     */
    function __construct(\Plasma\Drivers\MySQL\ProtocolParser $parser) {
        $this->parser = $parser;
    }
    
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
     * @param string  $buffer
     * @return string|bool
     * @throws \Plasma\Drivers\MySQL\Messages\ParseException
     */
    function parseMessage(string $buffer) {
        $handshake = $this->parser->getHandshakeMessage();
        if(!$handshake) {
            throw new \Plasma\Drivers\MySQL\Messages\ParseException('No handshake message when receiving ok response packet');
        }
        
        $this->statusFlags = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt2($buffer);
        $this->warningsCount = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt2($buffer);
        
        return $buffer;
    }
    
    /**
     * Get the parser which created this message.
     * @return \Plasma\Drivers\MySQL\ProtocolParser
     */
    function getParser(): \Plasma\Drivers\MySQL\ProtocolParser {
        return $this->parser;
    }
    
    /**
     * Sets the parser state, if necessary. If not, return `-1`.
     * @return int
     */
    function setParserState(): int {
        return \Plasma\Drivers\MySQL\ProtocolParser::STATE_OK;
    }
}
