<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL\AuthPlugins;

/**
 * Defines the interface for auth plugins.
 * @internal
 */
interface AuthPluginInterface {
    /**
     * Constructor. Receives the protocol parser and the handshake message.
     * @param \Plasma\Drivers\MySQL\ProtocolParser             $parser
     * @param \Plasma\Drivers\MySQL\Messages\HandshakeMessage  $handshake
     */
    function __construct(\Plasma\Drivers\MySQL\ProtocolParser $parser, \Plasma\Drivers\MySQL\Messages\HandshakeMessage $handshake);
    
    /**
     * Computes the auth response, including the length, for the handshake response.
     * @param string  $password
     * @return string
     */
    function getHandshakeAuth(string $password): string;
    
    /**
     * We received more auth data, so we send it into the auth plugin.
     * @param \Plasma\Drivers\MySQL\Messages\AuthMoreDataMessage  $message
     * @return \Plasma\Drivers\MySQL\Commands\CommandInterface
     * @throws \Plasma\Exception
     */
    function receiveMoreData(\Plasma\Drivers\MySQL\Messages\AuthMoreDataMessage $message): \Plasma\Drivers\MySQL\Commands\CommandInterface;
}
