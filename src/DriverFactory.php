<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL;

/**
 * The Driver Factory is responsible for creating the driver correctly.
 */
class DriverFactory implements \Plasma\DriverFactoryInterface {
    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $loop;
    
    /**
     * @var array
     */
    protected $options;
    
    /**
     * Constructor.
     *
     * The driver supports the following options:
     * ```
     * array(
     *     'connector' => ConnectorInstance, (a custom connector instance, which MUST return a `Connection` instance from the `react/socket` package)
     *     'tls.context' => array, (socket TLS context options)
     * )
     * ```
     *
     * @param \React\EventLoop\LoopInterface  $loop
     * @param array                           $options
     */
    function __construct(\React\EventLoop\LoopInterface $loop, array $options) {
        $this->loop = $loop;
        $this->options = $options;
    }
    
    /**
     * Creates a new driver instance.
     * @return \Plasma\DriverInterface
     */
    function createDriver(): \Plasma\DriverInterface {
        return (new \Plasma\Drivers\MySQL\Driver($this->loop, $this->options));
    }
}
