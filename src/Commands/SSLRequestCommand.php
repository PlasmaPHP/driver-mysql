<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
 */

namespace Plasma\Drivers\MySQL\Commands;

use Evenement\EventEmitterTrait;
use Plasma\Drivers\MySQL\Messages\HandshakeMessage;
use Plasma\Drivers\MySQL\ProtocolParser;

/**
 * SSLRequest command.
 * @internal
 */
class SSLRequestCommand implements CommandInterface {
    use EventEmitterTrait;
    
    /**
     * @var HandshakeMessage
     */
    protected $handshake;
    
    /**
     * @var int
     */
    protected $capability;
    
    /**
     * @var bool
     */
    protected $finished = false;
    
    /**
     * Constructor.
     * @param HandshakeMessage  $handshake
     * @param int               $capability
     */
    function __construct(HandshakeMessage $handshake, int $capability) {
        $this->handshake = $handshake;
        $this->capability = $capability;
    }
    
    /**
     * Get the encoded message for writing to the database connection.
     * @return string
     */
    function getEncodedMessage(): string {
        $maxPacketSize = ProtocolParser::CLIENT_MAX_PACKET_SIZE;
        $charsetNumber = ProtocolParser::CLIENT_CHARSET_NUMBER;
        
        $packet = \pack('VVc', $this->capability, $maxPacketSize, $charsetNumber);
        $packet .= \str_repeat("\x00", 23);
        
        $this->finished = true;
        return $packet;
    }
    
    /**
     * Sets the parser state, if necessary. If not, return `-1`.
     * @return int
     */
    function setParserState(): int {
        return ProtocolParser::STATE_HANDSHAKE;
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
        return false;
    }
    
    /**
     * Whether the sequence ID should be resetted.
     * @return bool
     */
    function resetSequence(): bool {
        return false;
    }
}
