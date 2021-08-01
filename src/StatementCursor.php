<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
 */

namespace Plasma\Drivers\MySQL;

use Plasma\CursorInterface;
use Plasma\Drivers\MySQL\Commands\FetchCommand;
use Plasma\Drivers\MySQL\Messages\EOFMessage;
use Plasma\Drivers\MySQL\Messages\OkResponseMessage;
use Plasma\Exception;
use React\Promise;
use React\Promise\PromiseInterface;

/**
 * Represents a Statement Cursor.
 */
class StatementCursor implements CursorInterface {
    /**
     * @var Driver
     */
    protected $driver;
    
    /**
     * @var Statement
     */
    protected $statement;
    
    /**
     * @var bool
     */
    protected $closed = false;
    
    /**
     * Constructor.
     * @param Driver     $driver
     * @param Statement  $statement
     */
    function __construct(Driver $driver, Statement $statement) {
        $this->driver = $driver;
        $this->statement = $statement;
    }
    
    /**
     * Destructor. Runs once the instance goes out of scope.
     * Please do not rely on the destructor to properly close your cursor.
     * ALWAYS explicitely close the cursor once you're done.
     * @codeCoverageIgnore
     */
    function __destruct() {
        if(!$this->closed) {
            $this->close()->then(null, function () {
                // Error during implicit close, close the session
                $this->driver->close();
            });
        }
    }
    
    /**
     * Whether the cursor has been closed.
     * @return bool
     */
    function isClosed(): bool {
        return $this->closed;
    }
    
    /**
     * Closes the cursor and frees the associated resources on the server.
     * Closing a cursor more than once has no effect.
     * @return PromiseInterface
     */
    function close(): PromiseInterface {
        if($this->closed) {
            return Promise\resolve();
        }
        
        $this->closed = true;
        
        return $this->statement->close();
    }
    
    /**
     * Fetches the given amount of rows using the cursor. Resolves with the row, an array of rows (if amount > 1), or false if no more results exist.
     * @param int  $amount
     * @return PromiseInterface
     * @throws \LogicException    Thrown if the driver or DBMS does not support cursors.
     * @throws Exception  Thrown if the underlying statement has been closed.
     */
    function fetch(int $amount = 1): PromiseInterface {
        if($this->closed) {
            return Promise\resolve(false);
        } elseif($this->statement->isClosed()) {
            throw new Exception('Underlying statement has been closed');
        }
        
        $fetch = new FetchCommand(
            $this->driver,
            $this,
            $this->statement->getID(),
            $this->statement->getQuery(),
            $this->statement->getParams(),
            array(),
            $this->statement->getColumns(),
            $amount
        );
        $this->driver->executeCommand($fetch);
        
        return $fetch->getPromise();
    }
    
    /**
     * Processes the OK or EOF message.
     * @param OkResponseMessage|EOFMessage  $message
     * @return void
     * @internal
     */
    function processOkMessage($message): void {
        if(($message->statusFlags & StatusFlags::SERVER_STATUS_LAST_ROW_SENT) > 0) {
            $this->close()->then(
                null,
                function (\Throwable $e) {
                    $this->driver->emit('error', array($e));
                }
            );
        }
    }
}
