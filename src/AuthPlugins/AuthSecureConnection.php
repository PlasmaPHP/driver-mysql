<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
 */

namespace Plasma\Drivers\MySQL\AuthPlugins;

use Plasma\BinaryBuffer;
use Plasma\Drivers\MySQL\Commands\CommandInterface;
use Plasma\Drivers\MySQL\Messages\AuthMoreDataMessage;
use Plasma\Drivers\MySQL\Messages\HandshakeMessage;
use Plasma\Drivers\MySQL\ProtocolParser;
use Plasma\Exception;

/**
 * Defines the interface for auth plugins.
 * @internal
 */
class AuthSecureConnection implements AuthPluginInterface {
    /**
     * @var ProtocolParser
     */
    protected $parser;
    
    /**
     * @var HandshakeMessage
     */
    protected $handshake;
    
    /**
     * Constructor. Receives the protocol parser and the handshake message.
     * @param ProtocolParser    $parser
     * @param HandshakeMessage  $handshake
     */
    function __construct(ProtocolParser $parser, HandshakeMessage $handshake) {
        $this->parser = $parser;
        $this->handshake = $handshake;
    }
    
    /**
     * Computes the auth response, including the length, for the handshake response.
     * @param string  $password
     * @return string
     */
    function getHandshakeAuth(string $password): string {
        if($password !== '') {
            $hash = \sha1($password, true);
            $str = $hash ^ \sha1($this->handshake->scramble.\sha1($hash, true), true);
            
            return BinaryBuffer::writeStringLength($str);
        }
        
        return "\x00";
    }
    
    /**
     * We received more auth data, so we send it into the auth plugin.
     * @param AuthMoreDataMessage  $message
     * @return CommandInterface
     * @throws Exception
     */
    function receiveMoreData(AuthMoreDataMessage $message): CommandInterface {
        throw new Exception('Auth plugin does not support auth more data');
    }
}
