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
     * @var \Plasma\Drivers\MySQL\AuthPlugins\AuthPluginInterface[]
     */
    protected static $authPlugins = array(
        \Plasma\Drivers\MySQL\ConnectionFlags::CLIENT_SECURE_CONNECTION => \Plasma\Drivers\MySQL\AuthPlugins\AuthSecureConnection::class
    );
    
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
        if(!\function_exists('stream_socket_enable_crypto')) {
            throw new \LogicException('Encryption is not supported on your platform');
        }
        
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
    
    /**
     * Adds an auth plugin. `$condition` is either an int (for server capabilities), or a string (for auth plugin name).
     * @param string|int  $condition
     * @param string      $classname  A class implementing `AuthPluginInterface`.
     * @return void
     * @throws \InvalidArgumentException
     */
    static function addAuthPlugin($condition, string $classname): void {
        if(isset(static::$authPlugins[$condition])) {
            throw new \InvalidArgumentException('Auth plugin for specified condition already exists');
        }
        
        if(!\in_array(\Plasma\Drivers\MySQL\AuthPlugins\AuthPluginInterface::class, \class_implements($classname, true))) {
            throw new \InvalidArgumentException('Specified auth plugin does not implement interface');
        }
        
        static::$authPlugins[$condition] = $classname;
    }
    
    /**
     * Get the registered auth plugins.
     * @return \Plasma\Drivers\MySQL\AuthPlugins\AuthPluginInterface[]
     */
    static function getAuthPlugins(): array {
        return static::$authPlugins;
    }
}
