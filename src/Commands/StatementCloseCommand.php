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
 * Statement Close command.
 * @internal
 */
class StatementCloseCommand extends PromiseCommand {
    /**
     * The identifier for this command.
     * @var int
     * @source
     */
    const COMMAND_ID = 0x19;
    
    /**
     * @var mixed
     */
    protected $id;
    
    /**
     * Constructor.
     * @param \Plasma\DriverInterface  $driver
     * @param mixed                    $id
     */
    function __construct(\Plasma\DriverInterface $driver, $id) {
        parent::__construct($driver);
        
        $this->driver = $driver;
        $this->id = $id;
    }
    
    /**
     * Get the encoded message for writing to the database connection.
     * @return string
     */
    function getEncodedMessage(): string {
        return \chr(static::COMMAND_ID).$this->id;
    }
    
    /**
     * Sends the next received value into the command.
     * @param mixed  $value
     * @return void
     */
    function onNext($value): void {
        $this->finished = true;
        if(!($value instanceof \Plasma\Drivers\MySQL\Messages\EOFMessage || $value instanceof \Plasma\Drivers\MySQL\Messages\OkResponseMessage)) {
            $this->emit('error',
                array(
                    (new \Plasma\Exception('Unknown on next value received of type '
                        .(\is_object($value) ? \get_class($value) : \gettype($value))))
                )
            );
        }
    }
    
    /**
     * Whether the sequence ID should be resetted.
     * @return bool
     */
    function resetSequence(): bool {
        return true;
    }
}
