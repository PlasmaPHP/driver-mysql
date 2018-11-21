<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL\Tests;

class DriverFactoryTest extends TestCase {
    function testCreateDriver() {
        $loop = \React\EventLoop\Factory::create();
        $factory = new \Plasma\Drivers\MySQL\DriverFactory($loop, array());
        
        $driver = $factory->createDriver();
        $this->assertInstanceOf(\Plasma\Drivers\MySQL\Driver::class, $driver);
    }
    
    function testGetSetFilesystem() {
        $fs = \Plasma\Drivers\MySQL\DriverFactory::getFilesystem();
        $this->assertNull($fs);
        
        $filesystem = $this->getMockBuilder(\React\Filesystem\FilesystemInterface::class)
            ->setMethods(array(
                'create',
                'createFromAdapter',
                'getSupportedAdapters',
                'getAdapter',
                'file',
                'dir',
                'link',
                'getContents',
                'setInvoker'
            ))
            ->getMock();
        
        $this->assertNull(\Plasma\Drivers\MySQL\DriverFactory::setFilesystem($filesystem));
        
        $fs2 = \Plasma\Drivers\MySQL\DriverFactory::getFilesystem();
        $this->assertSame($filesystem, $fs2);
        
        $this->assertNull(\Plasma\Drivers\MySQL\DriverFactory::setFilesystem(null));
        
        $fs3 = \Plasma\Drivers\MySQL\DriverFactory::getFilesystem();
        $this->assertNull($fs3);
    }
    
    function testAddAuthPlugin() {
        $plugs = \Plasma\Drivers\MySQL\DriverFactory::getAuthPlugins();
        $this->assertFalse(isset($plugs[__FUNCTION__]));
        
        $this->assertNull(\Plasma\Drivers\MySQL\DriverFactory::addAuthPlugin(__FUNCTION__, \get_class($this->getAuthPlugin())));
        
        $plugs2 = \Plasma\Drivers\MySQL\DriverFactory::getAuthPlugins();
        $this->assertTrue(isset($plugs2[__FUNCTION__]));
    }
    
    function testAddAuthPluginExisting() {
        $plugs = \Plasma\Drivers\MySQL\DriverFactory::getAuthPlugins();
        $this->assertFalse(isset($plugs[__FUNCTION__]));
        
        \Plasma\Drivers\MySQL\DriverFactory::addAuthPlugin(__FUNCTION__, \get_class($this->getAuthPlugin()));
        
        $plugs2 = \Plasma\Drivers\MySQL\DriverFactory::getAuthPlugins();
        $this->assertTrue(isset($plugs2[__FUNCTION__]));
        
        $this->expectException(\InvalidArgumentException::class);
        \Plasma\Drivers\MySQL\DriverFactory::addAuthPlugin(__FUNCTION__, \get_class($this->getAuthPlugin()));
    }
    
    function testAddAuthPluginInterfaceMismatch() {
        $this->expectException(\InvalidArgumentException::class);
        \Plasma\Drivers\MySQL\DriverFactory::addAuthPlugin(__FUNCTION__, \stdClass::class);
    }
    
    function getAuthPlugin() {
        return $this->getMockBuilder(\Plasma\Drivers\MySQL\AuthPlugins\AuthPluginInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(array(
                '__construct',
                'getHandshakeAuth',
                'receiveMoredata'
            ))
            ->getMock();
    }
}
