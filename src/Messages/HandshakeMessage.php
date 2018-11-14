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
 * Represents a Handshake Message.
 * @internal
 */
class HandshakeMessage implements \Plasma\Drivers\MySQL\Messages\MessageInterface {
    /**
     * The Handshake Protocol version.
     * @var int
     */
    public $protocolVersion;
    
    /**
     * A human readable server version.
     * @var string
     */
    public $serverVersion;
    
    /**
     * The connection ID.
     * @var int
     */
    public $connectionID;
    
    /**
     * Authentication data.
     * @var string
     */
    public $scramble;
    
    /**
     * The server's capability flags.
     * @var int
     */
    public $capability;
    
    /**
     * The character set.
     * @var int|null
     */
    public $characterSet;
    
    /**
     * The status flags.
     * @var int|null
     * @see \Plasma\Drivers\MySQL\StatusFlags
     */
    public $statusFlags;
    
    /**
     * The authentication plugin name.
     * @var string|null
     */
    public $authPluginName;
    
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
        return "";
    }
    
    /**
     * Parses the message, once the complete string has been received.
     * Returns false if not enough data has been received, or the remaining buffer.
     * @param string  $buffer
     * @return string|bool
     * @throws \Plasma\Drivers\MySQL\Messages\ParseException
     */
    function parseMessage(string $buffer) {
        $protocol = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt1($buffer);
        
        switch($protocol) {
            case 0x0A:
                return $this->parseProtocol10($buffer);
            break;
            default:
                $exception = new \Plasma\Drivers\MySQL\Messages\ParseException('Unsupported protocol version');
                $exception->setState(\Plasma\Drivers\MySQL\ProtocolParser::STATE_HANDSHAKE_ERROR);
                $exception->setBuffer('');
                
                throw $exception;
            break;
        }
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
        return \Plasma\Drivers\MySQL\ProtocolParser::STATE_HANDSHAKE;
    }
    
    /**
     * Parses the message as Handshake V10.
     * @return string|bool
     * @throws \Plasma\Drivers\MySQL\Messages\ParseException
     */
    protected function parseProtocol10(string $buffer) {
        $versionLength = \strpos($buffer, "\x00");
        if($versionLength === false) {
            return false;
        }
        
        $buffLength = \strlen($buffer);
        $minLength = $versionLength + 1 + 15;
        $moreDataLength = $buffLength - $minLength;
        
        if($buffLength < $minLength) {
            return false;
        } elseif($moreDataLength > 0 && $moreDataLength < 16) {
            return false;
        }
        
        $serverVersion = \Plasma\Drivers\MySQL\Messages\MessageUtility::readStringNull($buffer);
        $connectionID = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt4($buffer);
        $scramble = \Plasma\Drivers\MySQL\Messages\MessageUtility::readStringLength($buffer, 8); // Part 1
        
        $buffer = \substr($buffer, 1); // Remove filler byte
        
        $capability = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt2($buffer);
        
        $this->protocolVersion = 10;
        $this->serverVersion = $serverVersion;
        $this->connectionID = $connectionID;
        $this->scramble = $scramble;
        
        if(\strlen($buffer) > 0) {
            $characterSet = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt1($buffer);
            $statusFlags = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt2($buffer);
            $capability += \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt2($buffer) << 16;
            
            if(($capability & \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_PROTOCOL_41) === 0) {
                $exception = new \Plasma\Drivers\MySQL\Messages\ParseException('The old MySQL protocol 320 is not supported');
                $exception->setState(\Plasma\Drivers\MySQL\ProtocolParser::STATE_HANDSHAKE_ERROR);
                $exception->setBuffer('');
                
                throw $exception;
            }
            
            if(($capability & \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_PLUGIN_AUTH) !== 0) {
                $authDataLength = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt1($buffer);
            } else {
                $authDataLength  = 0;
                $buffer = \substr($buffer, 1);
            }
            
            $buffer = \substr($buffer, 10);
            
            if(($capability & \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_SECURE_CONNECTION) !== 0) {
                $len = \max(13, ($authDataLength - 8));
                $this->scramble .= \rtrim(\Plasma\Drivers\MySQL\Messages\MessageUtility::readStringLength($buffer, $len), "\x00");
            }
            
            if(($capability & \Plasma\Drivers\MySQL\CapabilityFlags::CLIENT_PLUGIN_AUTH) !== 0) {
                $authPluginName = \Plasma\Drivers\MySQL\Messages\MessageUtility::readStringNull($buffer);
            }
            
            $this->characterSet = $characterSet;
            $this->statusFlags = $statusFlags;
            $this->authPluginName = $authPluginName ?? null;
        }
        
        return $buffer;
    }
}
