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
     * Get the identifier for the packet.
     * @return string
     */
    static function getID(): string {
        return "\xFF";
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
        $nameLength = \strpos($buffer, "\x00");
        if($nameLength === false) {
            return false;
        }
        
        $this->errorCode = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt2($buffer);
        
        $handshake = $parser->getHandshakeMessage();
        if(!$handshake || $parser->getState() == \Plasma\Drivers\MySQL\ProtocolParser::STATE_HANDSHAKE) {
            $exception = new \Plasma\Drivers\MySQL\Messages\ParseException($buffer, $this->errorCode);
            $exception->setState(\Plasma\Drivers\MySQL\ProtocolParser::STATE_HANDSHAKE_ERROR);
            $exception->setBuffer('');
            
            throw $exception;
        }
        
        $this->sqlStateMarker = \Plasma\Drivers\MySQL\Messages\MessageUtility::readStringLength($buffer, 1);
        $this->sqlState = \Plasma\Drivers\MySQL\Messages\MessageUtility::readStringLength($buffer, 5);
        $this->errorMessage = $buffer;
        
        return '';
    }
    
    /**
     * Sets the parser state, if necessary. If not, return `-1`.
     * @return int
     */
    function setParserState(): int {
        return \Plasma\Drivers\MySQL\ProtocolParser::STATE_OK;
    }
}
