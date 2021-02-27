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
 * Represents an Error Response Message.
 */
class ErrResponseMessage implements MessageInterface {
    /**
     * Error code.
     * @var int
     */
    public $errorCode;
    
    /**
     * SQL State marker, if any.
     * @var string|null
     */
    public $sqlStateMarker;
    
    /**
     * SQL State, if any.
     * @var string|null
     */
    public $sqlState;
    
    /**
     * Error message.
     * @var string
     */
    public $errorMessage;
    
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
        return "\xFF";
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
        $this->errorCode = $buffer->readInt2();
        
        $handshake = $this->parser->getHandshakeMessage();
        if(!$handshake || $this->parser->getState() === ProtocolParser::STATE_HANDSHAKE) {
            $exception = new ParseException($buffer->getContents(), $this->errorCode);
            $exception->setState(ProtocolParser::STATE_HANDSHAKE_ERROR);
            $exception->setBuffer('');
            
            $buffer->clear();
            throw $exception;
        }
        
        $this->sqlStateMarker = $buffer->readStringLength(1);
        $this->sqlState = $buffer->readStringLength(5);
        $this->errorMessage = $buffer->getContents();
        
        $buffer->clear();
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
