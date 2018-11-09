<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL\AuthPlugins;

/**
 * Defines the interface for auth plugins.
 * @internal
 */
class AuthSecureConnection implements AuthPluginInterface {
    /**
     * @var \Plasma\Drivers\MySQL\ProtocolParser
     */
    protected $parser;
    
    /**
     * @var \Plasma\Drivers\MySQL\Messages\HandshakeMessage
     */
    protected $handshake;
    
    /**
     * Constructor. Receives the protocol parser and the handshake message.
     * @param \Plasma\Drivers\MySQL\ProtocolParser             $parser
     * @param \Plasma\Drivers\MySQL\Messages\HandshakeMessage  $handshake
     */
    function __construct(\Plasma\Drivers\MySQL\ProtocolParser $parser, \Plasma\Drivers\MySQL\Messages\HandshakeMessage $handshake) {
        $this->parser = $parser;
        $this->handshake = $handshake;
    }

    /**
     * Computes the auth response, including the length, for the handshake response.
     * @param string  $password
     * @return string
     */
    function getHandshakeAuth(string $password): string {
        if(!empty($this->password)) {
            $hash = \sha1($password, true);
            $str = $hash ^ \sha1($scramble.\sha1($hash, true), true);
            
            return \Plasma\Drivers\MySQL\Messages\MessageUtility::buildStringLength($str);
        }
        
        return "\x00";
    }
}
