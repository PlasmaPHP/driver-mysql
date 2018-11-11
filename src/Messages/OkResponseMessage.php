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
 * Represents a Ok Response Message.
 * @internal
 */
class OkResponseMessage implements \Plasma\Drivers\MySQL\Messages\MessageInterface {
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
        return "\x00";
    }
    
    /**
     * Parses the message, once the complete string has been received.
     * Returns false if not enough data has been received, or the remaining buffer.
     * @param string  $buffer
     * @return string|bool
     * @throws \Plasma\Drivers\MySQL\Messages\ParseException
     */
    function parseMessage(string $buffer) {
        $nameLength = \strpos($buffer, "\x00");
        if($nameLength === false) {
            return false;
        }
        
        $affectedRows = \Plasma\Drivers\MySQL\Messages\MessageUtility::readIntLength($buffer);
        $lastInsertedID = \Plasma\Drivers\MySQL\Messages\MessageUtility::readIntLength($buffer);
        
        $handshake = $this->parser->getHandshakeMessage();
        if(!$handshake) {
            throw new \Plasma\Drivers\MySQL\Messages\ParseException('No handshake message when receiving ok response packet');
        }
        
        $statusFlags = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt2($buffer);
        $warningsCount = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt2($buffer);
        
        if(($handshake->capability & \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_SESSION_TRACK) !== 0) {
            $sessionInfo = \Plasma\Drivers\MySQL\Messages\MessageUtility::readStringLength($buffer);
            
            if(($statusFlags & \Plasma\Drivers\MySQL\StatusFlags::SERVER_SESSION_STATE_CHANGED) !== 0) {
                $sessionStateChanges = \Plasma\Drivers\MySQL\Messages\MessageUtility::readStringLength($buffer);
            } else {
                $sessionStateChanges = null;
            }
            
            $info = null;
        } else {
            $sessionInfo = null;
            $sessionStateChanges = null;
            $info = $buffer;
        }
        
        $this->affectedRows = $affectedRows;
        $this->lastInsertedID = $lastInsertedID;
        
        $this->statusFlags = $statusFlags;
        $this->warningsCount = $warningsCount;
        
        $this->sessionInfo = $sessionInfo;
        $this->sessionStateChanges = $sessionStateChanges;
        $this->info = $info;
        
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
