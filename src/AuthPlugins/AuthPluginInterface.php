<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
 */

namespace Plasma\Drivers\MySQL\AuthPlugins;

use Plasma\Drivers\MySQL\Commands\CommandInterface;
use Plasma\Drivers\MySQL\Messages\AuthMoreDataMessage;
use Plasma\Drivers\MySQL\Messages\HandshakeMessage;
use Plasma\Drivers\MySQL\ProtocolParser;
use Plasma\Exception;

/**
 * Defines the interface for auth plugins.
 * @internal
 */
interface AuthPluginInterface {
    /**
     * Constructor. Receives the protocol parser and the handshake message.
     * @param ProtocolParser    $parser
     * @param HandshakeMessage  $handshake
     */
    function __construct(ProtocolParser $parser, HandshakeMessage $handshake);
    
    /**
     * Computes the auth response, including the length, for the handshake response.
     * @param string  $password
     * @return string
     */
    function getHandshakeAuth(string $password): string;
    
    /**
     * We received more auth data, so we send it into the auth plugin.
     * @param AuthMoreDataMessage  $message
     * @return CommandInterface
     * @throws Exception
     */
    function receiveMoreData(AuthMoreDataMessage $message): CommandInterface;
}
