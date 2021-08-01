<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
 * @noinspection PhpUnhandledExceptionInspection
*/

namespace Plasma\Drivers\MySQL\Tests;

use Plasma\Drivers\MySQL\AuthPlugins\AuthPluginInterface;
use Plasma\Drivers\MySQL\Driver;
use Plasma\Drivers\MySQL\DriverFactory;
use React\EventLoop\Factory;
use React\Filesystem\FilesystemInterface;

class DriverFactoryTest extends TestCase {
    function testCreateDriver() {
        $loop = Factory::create();
        $factory = new DriverFactory($loop, array());
        
        $driver = $factory->createDriver();
        self::assertInstanceOf(Driver::class, $driver);
    }
    
    function testGetSetFilesystem() {
        $fs = DriverFactory::getFilesystem();
        self::assertNull($fs);
        
        $filesystem = $this->getMockBuilder(FilesystemInterface::class)
            ->setMethods(array(
                'create',
                'createFromAdapter',
                'getSupportedAdapters',
                'getAdapter',
                'file',
                'dir',
                'link',
                'getContents',
                'setInvoker',
                'constructLink'
            ))
            ->getMock();
        
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        self::assertNull(DriverFactory::setFilesystem($filesystem));
        
        $fs2 = DriverFactory::getFilesystem();
        self::assertSame($filesystem, $fs2);
        
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        self::assertNull(DriverFactory::setFilesystem(null));
        
        $fs3 = DriverFactory::getFilesystem();
        self::assertNull($fs3);
    }
    
    function testAddAuthPlugin() {
        $plugs = DriverFactory::getAuthPlugins();
        self::assertFalse(isset($plugs[__FUNCTION__]));
        
        /** @noinspection PhpVoidFunctionResultUsedInspection */
        self::assertNull(DriverFactory::addAuthPlugin(__FUNCTION__, \get_class($this->getAuthPlugin())));
        
        $plugs2 = DriverFactory::getAuthPlugins();
        self::assertTrue(isset($plugs2[__FUNCTION__]));
    }
    
    function testAddAuthPluginExisting() {
        $plugs = DriverFactory::getAuthPlugins();
        self::assertFalse(isset($plugs[__FUNCTION__]));
        
        DriverFactory::addAuthPlugin(__FUNCTION__, \get_class($this->getAuthPlugin()));
        
        $plugs2 = DriverFactory::getAuthPlugins();
        self::assertTrue(isset($plugs2[__FUNCTION__]));
        
        $this->expectException(\InvalidArgumentException::class);
        DriverFactory::addAuthPlugin(__FUNCTION__, \get_class($this->getAuthPlugin()));
    }
    
    function testAddAuthPluginInterfaceMismatch() {
        $this->expectException(\InvalidArgumentException::class);
        DriverFactory::addAuthPlugin(__FUNCTION__, \stdClass::class);
    }
    
    function getAuthPlugin() {
        return $this->getMockBuilder(AuthPluginInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(array(
                '__construct',
                'getHandshakeAuth',
                'receiveMoredata'
            ))
            ->getMock();
    }
}
