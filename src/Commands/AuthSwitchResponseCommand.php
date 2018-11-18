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
 * Auth Switch Response command.
 * @internal
 * @codeCoverageIgnore
 */
class AuthSwitchResponseCommand implements CommandInterface {
    use \Evenement\EventEmitterTrait;
    
    /**
     * @var \Plasma\Drivers\MySQL\Messages\AuthSwitchRequestMessage
     */
    protected $message;
    
    /**
     * @var \Plasma\Drivers\MySQL\AuthPlugins\AuthPluginInterface
     */
    protected $plugin;
    
    /**
     * @var string
     */
    protected $password;
    
    /**
     * @var bool
     */
    protected $finished = false;
    
    /**
     * Constructor.
     * @param \Plasma\Drivers\MySQL\Messages\AuthSwitchRequestMessage  $message
     * @param \Plasma\Drivers\MySQL\AuthPlugins\AuthPluginInterface    $plugin
     * @param string                                                   $password
     */
    function __construct(\Plasma\Drivers\MySQL\Messages\AuthSwitchRequestMessage $message, \Plasma\Drivers\MySQL\AuthPlugins\AuthPluginInterface $plugin, string $password) {
        $this->message = $message;
        $this->plugin = $plugin;
        $this->password = $password;
    }
    
    /**
     * Get the encoded message for writing to the database connection.
     * @return string
     */
    function getEncodedMessage(): string {
        $this->finished = true;
        return $this->plugin->getHandshakeAuth($this->password);
    }
    
    /**
     * Sets the parser state, if necessary. If not, return `-1`.
     * @return int
     */
    function setParserState(): int {
        return \Plasma\Drivers\MySQL\ProtocolParser::STATE_AUTH_SENT;
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
