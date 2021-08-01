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

/**
 * Represents a Handshake Message.
 */
class HandshakeMessage implements MessageInterface {
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
        return "";
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
        $protocol = $buffer->readInt1();
        
        switch($protocol) {
            case 0x0A:
                $this->parseProtocol10($buffer);
            break;
            default:
                $exception = new ParseException('Unsupported protocol version');
                $exception->setState(ProtocolParser::STATE_HANDSHAKE_ERROR);
                $exception->setBuffer('');
                
                throw $exception;
        }
        
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
        return ProtocolParser::STATE_HANDSHAKE;
    }
    
    /**
     * Parses the message as Handshake V10.
     * @param BinaryBuffer  $buffer
     * @return bool
     * @throws ParseException
     */
    protected function parseProtocol10(BinaryBuffer $buffer): bool {
        $versionLength = \strpos($buffer->getContents(), "\x00");
        if($versionLength === false) {
            return false;
        }
        
        $buffLength = $buffer->getSize();
        $minLength = $versionLength + 1 + 15;
        $moreDataLength = $buffLength - $minLength;
        
        if($buffLength < $minLength) {
            return false;
        } elseif($moreDataLength > 0 && $moreDataLength < 16) {
            return false;
        }
        
        $this->protocolVersion = 10;
        $this->serverVersion = $buffer->readStringNull();
        $this->connectionID = $buffer->readInt4();
        $this->scramble = $buffer->readStringLength(8); // Part 1
        
        $buffer->readStringLength(1); // Remove filler byte
        
        $this->capability = $buffer->readInt2();
        
        if($buffer->getSize() > 0) {
            $this->characterSet = $buffer->readInt1();
            $this->statusFlags = $buffer->readInt2();
            $this->capability += $buffer->readInt2() << 16;
            
            if(($this->capability & CapabilityFlags::CLIENT_PROTOCOL_41) === 0) {
                $exception = new ParseException('The old MySQL protocol 320 is not supported');
                $exception->setState(ProtocolParser::STATE_HANDSHAKE_ERROR);
                $exception->setBuffer('');
                
                throw $exception;
            }
            
            if(($this->capability & CapabilityFlags::CLIENT_PLUGIN_AUTH) !== 0) {
                $authDataLength = $buffer->readInt1();
            } else {
                $authDataLength = 0;
                $buffer->readStringLength(1);
            }
            
            $buffer->readStringLength(10);
            
            if(($this->capability & CapabilityFlags::CLIENT_SECURE_CONNECTION) !== 0) {
                $len = \max(13, ($authDataLength - 8));
                $this->scramble .= \rtrim($buffer->readStringLength($len), "\x00");
            }
            
            if(($this->capability & CapabilityFlags::CLIENT_PLUGIN_AUTH) !== 0) {
                $this->authPluginName = $buffer->readStringNull();
            }
        }
        
        return true;
    }
}
