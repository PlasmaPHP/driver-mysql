<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL\Commands;

/**
 * Handshake Response command.
 * @internal
 */
class HandshakeResponseCommand implements CommandInterface {
    use \Evenement\EventEmitterTrait;
    
    /**
     * @var \Plasma\Drivers\MySQL\ProtocolParser
     */
    protected $parser;
    
    /**
     * @var \Plasma\Drivers\MySQL\Messages\HandshakeMessage
     */
    protected $handshake;
    
    /**
     * @var int
     */
    protected $capability;
    
    /**
     * @var string|null
     */
    protected $plugin;
    
    /**
     * @var string
     */
    protected $user;
    
    /**
     * @var string
     */
    protected $password;
    
    /**
     * @var string
     */
    protected $database;
    
    /**
     * @var bool
     */
    protected $finished = false;
    
    /**
     * Constructor.
     * @param \Plasma\Drivers\MySQL\ProtocolParser             $parser
     * @param \Plasma\Drivers\MySQL\Messages\HandshakeMessage  $handshake
     * @param int                                              $capability
     * @param string|null                                      $plugin
     * @param string                                           $user
     * @param string                                           $password
     * @param string                                           $database
     */
    function __construct(
        \Plasma\Drivers\MySQL\ProtocolParser $parser, \Plasma\Drivers\MySQL\Messages\HandshakeMessage $handshake,
        int $capability, ?string $plugin, string $user, string $password, string $database
    ) {
        $this->parser = $parser;
        $this->handshake = $handshake;
        $this->capability = $capability;
        $this->plugin = $plugin;
        
        $this->user = $user;
        $this->password = $password;
        $this->database = $database;
    }
    
    /**
     * Get the encoded message for writing to the database connection.
     * @return string
     */
    function getEncodedMessage(): string {
        $maxPacketSize = \Plasma\Drivers\MySQL\ProtocolParser::CLIENT_MAX_PACKET_SIZE;
        $charsetNumber = \Plasma\Drivers\MySQL\ProtocolParser::CLIENT_CHARSET_NUMBER;
        
        $packet = \pack('VVc', $this->capability, $maxPacketSize, $charsetNumber);
        $packet .= \str_repeat("\x00", 23);
        $packet .= $this->user."\x00";
        
        if($this->plugin !== null) {
            $plug = $this->plugin;
            
            /** @var \Plasma\Drivers\MySQL\AuthPlugins\AuthPluginInterface  $auth */
            $auth = new $plug($this->parser, $this->handshake);
            
            $packet .= $auth->getHandshakeAuth($this->password);
        }
        
        if($this->database !== '') {
            $packet .= $this->database."\x00";
        }
        
        if($this->plugin !== null) {
            $packet .= $this->handshake->authPluginName."\x00";
        }
        
        $this->finished = true;
        return $packet;
    }
    
    /**
     * Sets the parser state, if necessary. If not, return `-1`.
     * @return int
     */
    function setParserState(): int {
        return \Plasma\Drivers\MySQL\ProtocolParser::STATE_AUTH;
    }
    
    /**
     * Sets the command as completed. This state gets reported back to the user.
     * @return void
     */
    function onComplete(): void {
        $this->finished = true;
        $this->emit('end');
    }
    
    /**
     * Sets the command as errored. This state gets reported back to the user.
     * @param \Throwable  $throwable
     * @return void
     */
    function onError(\Throwable $throwable): void {
        $this->finished = true;
        $this->emit('error', array($throwable));
    }
    
    /**
     * Sends the next received value into the command.
     * @param mixed  $value
     * @return void
     */
    function onNext($value): void {
        // Nothing to do
    }
    
    /**
     * Whether the command has finished.
     * @return bool
     */
    function hasFinished(): bool {
        return $this->finished;
    }
    
    /**
     * Whether this command sets the connection as busy.
     * @return bool
     */
    function waitForCompletion(): bool {
        return true;
    }
    
    /**
     * Whether the sequence ID should be resetted.
     * @return bool
     */
    function resetSequence(): bool {
        return false;
    }
}
