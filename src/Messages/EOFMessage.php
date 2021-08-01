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
 * Represents an EOF Message.
 */
class EOFMessage implements MessageInterface {
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
     * @throws ParseException
     * @internal
     */
    function parseMessage(BinaryBuffer $buffer): bool {
        $handshake = $this->parser->getHandshakeMessage();
        if(!$handshake) {
            throw new ParseException('No handshake message when receiving ok response packet');
        }
        
        $this->statusFlags = $buffer->readInt2();
        $this->warningsCount = $buffer->readInt2();
        
        return true;
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
        return ProtocolParser::STATE_OK;
    }
}
