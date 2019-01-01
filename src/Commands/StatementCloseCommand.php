<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
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
        $this->id = $id;
    }
    
    /**
     * Get the encoded message for writing to the database connection.
     * @return string
     */
    function getEncodedMessage(): string {
        $this->finished = true;
        return \chr(static::COMMAND_ID).\Plasma\BinaryBuffer::writeInt4($this->id);
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
        return true;
    }
}
