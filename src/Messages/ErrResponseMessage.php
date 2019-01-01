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
 * Represents an Err Response Message.
 * @internal
 */
class ErrResponseMessage implements \Plasma\Drivers\MySQL\Messages\MessageInterface {
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
        return "\xFF";
    }
    
    /**
     * Parses the message, once the complete string has been received.
     * Returns false if not enough data has been received, or the remaining buffer.
     * @param \Plasma\BinaryBuffer  $buffer
     * @return bool
     * @throws \Plasma\Drivers\MySQL\Messages\ParseException
     */
    function parseMessage(\Plasma\BinaryBuffer $buffer): bool {
        $this->errorCode = $buffer->readInt2();
        
        $handshake = $this->parser->getHandshakeMessage();
        if(!$handshake || $this->parser->getState() == \Plasma\Drivers\MySQL\ProtocolParser::STATE_HANDSHAKE) {
            $exception = new \Plasma\Drivers\MySQL\Messages\ParseException($buffer->getContents(), $this->errorCode);
            $exception->setState(\Plasma\Drivers\MySQL\ProtocolParser::STATE_HANDSHAKE_ERROR);
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
