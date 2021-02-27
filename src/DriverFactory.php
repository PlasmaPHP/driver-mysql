<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
 */

namespace Plasma\Drivers\MySQL;

use Plasma\DriverFactoryInterface;
use Plasma\DriverInterface;
use Plasma\Drivers\MySQL\AuthPlugins\AuthPluginInterface;
use Plasma\Drivers\MySQL\AuthPlugins\AuthSecureConnection;
use Plasma\Exception;
use Plasma\Types\TypeExtensionsManager;
use React\EventLoop\LoopInterface;
use React\Filesystem\FilesystemInterface;

/**
 * The Driver Factory is responsible for creating the driver correctly.
 */
class DriverFactory implements DriverFactoryInterface {
    /**
     * @var LoopInterface
     */
    protected $loop;
    
    /**
     * @var array
     */
    protected $options;
    
    /**
     * @var AuthPluginInterface[]
     */
    protected static $authPlugins = array(
        CapabilityFlags::CLIENT_SECURE_CONNECTION => AuthSecureConnection::class,
        'mysql_native_password' => AuthSecureConnection::class
    );
    
    /**
     * @var FilesystemInterface|null
     */
    protected static $filesystem;
    
    /**
     * Constructor.
     *
     * The driver supports the following options:
     * ```
     * array(
     *     'connector' => ConnectorInstance, (a custom connector instance, which MUST return a `Connection` instance from the `react/socket` package)
     *     'characters.set' => string, (the character set to use, defaults to utf8mb4)
     *     'characters.collate' => string, (the collate to use, defaults to the charset default)
     *     'compression.enable' => bool, (whether compression should be used if available, defaults to true)
     *     'localInFile.enable' => bool, (whether local in file requests are enabled, defaults to false, see issue #16)
     *     'tls.context' => array, (socket TLS context options)
     *     'tls.force' => bool, (whether non-localhost connections are forced to use TLS, defaults to true)
     *     'tls.forceLocal' => bool, (whether localhost connections are forced to use TLS, defaults to false)
     * )
     * ```
     *
     * @param LoopInterface  $loop
     * @param array          $options
     */
    function __construct(LoopInterface $loop, array $options) {
        if(!\function_exists('stream_socket_enable_crypto')) {
            throw new \LogicException('Encryption is not supported on your platform');
        }
        
        try {
            TypeExtensionsManager::registerManager('driver-mysql', null);
        } catch (Exception $e) {
            /* One already exists, continue regardless */
        }
        
        $this->loop = $loop;
        $this->options = $options;
    }
    
    /**
     * Creates a new driver instance.
     * @return DriverInterface
     */
    function createDriver(): DriverInterface {
        return (new Driver($this->loop, $this->options));
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
        
        if(!\in_array(AuthPluginInterface::class, \class_implements($classname, true), true)) {
            throw new \InvalidArgumentException('Specified auth plugin does not implement interface');
        }
        
        static::$authPlugins[$condition] = $classname;
    }
    
    /**
     * Get the registered auth plugins.
     * @return AuthPluginInterface[]
     */
    static function getAuthPlugins(): array {
        return static::$authPlugins;
    }
    
    /**
     * Set the React Filesystem to use.
     * @param FilesystemInterface|null  $filesystem
     * @return void
     */
    static function setFilesystem(?FilesystemInterface $filesystem): void {
        static::$filesystem = $filesystem;
    }
    
    /**
     * Get the React Filesystem, or null. The filesystem must be set by the user, in order to not get `null`.
     * @return FilesystemInterface|null
     */
    static function getFilesystem(): ?FilesystemInterface {
        return static::$filesystem;
    }
}
