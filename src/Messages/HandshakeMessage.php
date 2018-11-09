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
     * @var int
     */
    public $protocolVersion;
    
    /**
     * @var string
     */
    public $serverVersion;
    
    /**
     * @var int
     */
    public $connectionID;
    
    /**
     * @var string
     */
    public $scramble;
    
    /**
     * @var int
     */
    public $capability;
    
    /**
     * @var int|null
     */
    public $characterSet;
    
    /**
     * @var int|null
     */
    public $statusFlags;
    
    /**
     * @var string|null
     */
    public $authPluginName;
    
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
     * @return string|bool
     * @throws \Plasma\Drivers\MySQL\Messages\ParseException
     */
    function parseMessage(string $buffer) {
        $version = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt1($buffer);
        if($version === 0xFF) {
            $errno = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt2($buffer);
            $errmsg = $buffer;
            
            $exception = new \Plasma\Drivers\MySQL\Messages\ParseException($errmsg, $errno);
            $exception->setState(\Plasma\Drivers\MySQL\ProtocolParser::STATE_HANDSHAKE_ERROR);
            $exception->setBuffer('');
            
            throw $exception;
        }
        
        switch($protocol) {
            case 0x0a:
                return $this->parseProtocol10($buffer);
            break;
            case 0x09:
                return $this->parseProtocol9($buffer);
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
        $scramble = \Plasma\Drivers\MySQL\Messages\MessageUtility::readStringNull($buffer); // Part 1
        
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
            
            if(($capability & \Plasma\Drivers\MySQL\ConnectionFlags::CLIENT_SECURE_CONNECTION) !== 0) {
                $authDataLength = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt1($buffer);
                $len = \max(13, ($authDataLength - 8));
                
                $scramble .= \Plasma\Drivers\MySQL\Messages\MessageUtility::readStringLength($buffer, $len);
            } else {
                $buffer = \substr($buffer, 1);
            }
            
            if(($capability & \Plasma\Drivers\MySQL\ConnectionFlags::CLIENT_PLUGIN_AUTH) !== 0) {
                $authPluginName = \Plasma\Drivers\MySQL\Messages\MessageUtility::readStringNull($buffer);
            }
            
            $this->characterSet = $characterSet;
            $this->statusFlags = $statusFlags;
            $this->authPluginName = $authPluginName;
        }
        
        return $buffer;
    }
    
    /**
     * Parses the message as Handshake V9.
     * @return string|bool
     * @throws \Plasma\Drivers\MySQL\Messages\ParseException
     */
    protected function parseProtocol9(string $buffer) {
        $versionLength = \strpos($buffer, "\x00");
        if($versionLength === false) {
            return false;
        }
        
        $versionLength = \strpos($buffer, "\x00", ($versionLength + 4));
        if($versionLength === false) {
            return false;
        }
        
        $serverVersion = \Plasma\Drivers\MySQL\Messages\MessageUtility::readStringNull($buffer);
        $connectionID = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt4($buffer);
        $scramble = \Plasma\Drivers\MySQL\Messages\MessageUtility::readStringNull($buffer);
        
        $this->protocolVersion = 9;
        $this->serverVersion = $serverVersion;
        $this->connectionID = $connectionID;
        $this->scramble = $scramble;
        
        return $buffer;
    }
}
