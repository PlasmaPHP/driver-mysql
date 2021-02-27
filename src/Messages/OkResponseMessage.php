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
use Plasma\Drivers\MySQL\CapabilityFlags;
use Plasma\Drivers\MySQL\ProtocolParser;
use Plasma\Drivers\MySQL\StatusFlags;

/**
 * Represents an Ok Response Message.
 */
class OkResponseMessage implements MessageInterface {
    /**
     * Count of affected rows by the last query.
     * @var int
     */
    public $affectedRows;
    
    /**
     * Last inserted ID by the last `INSERT` query.
     * @var int
     */
    public $lastInsertedID;
    
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
     * Server session information, if any.
     * @var string|null
     */
    public $sessionInfo;
    
    /**
     * Server session state changes, if any.
     * @var string|null
     */
    public $sessionStateChanges;
    
    /**
     * Human readable status information, if any.
     * @var string|null
     */
    public $info;
    
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
        return "\x00";
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
        try {
            $handshake = $this->parser->getHandshakeMessage();
            if(!$handshake) {
                throw new ParseException('No handshake message when receiving ok response packet');
            }
            
            $this->affectedRows = $buffer->readIntLength();
            $this->lastInsertedID = $buffer->readIntLength();
            
            $this->statusFlags = $buffer->readInt2();
            $this->warningsCount = $buffer->readInt2();
            
            if(($handshake->capability & CapabilityFlags::CLIENT_SESSION_TRACK) !== 0) {
                if($buffer->getSize() > 0) {
                    $this->sessionInfo = $buffer->readStringLength();
                    
                    if(($this->statusFlags & StatusFlags::SERVER_SESSION_STATE_CHANGED) !== 0) {
                        $this->sessionStateChanges = $buffer->readStringLength();
                    }
                }
            } else {
                $this->info = $buffer->getContents();
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
        return ProtocolParser::STATE_OK;
    }
}
