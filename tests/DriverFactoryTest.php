<?php
/**
 * Plasma Core component
 * Copyright 2018 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/core/blob/master/LICENSE
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
    }
    
    function testAddAuthPlugin() {
        $plugs = \Plasma\Drivers\MySQL\DriverFactory::getAuthPlugins();
        $this->assertFalse(isset($plugs[__FUNCTION__]));
        
        $this->assertNull(\Plasma\Drivers\MySQL\DriverFactory::addAuthPlugin(__FUNCTION__, $this->getAuthPlugin()));
        
        $plugs2 = \Plasma\Drivers\MySQL\DriverFactory::getAuthPlugins();
        $this->assertTrue(isset($plugs2[__FUNCTION__]));
    }
    
    function testAddAuthPluginExisting() {
        $plugs = \Plasma\Drivers\MySQL\DriverFactory::getAuthPlugins();
        $this->assertFalse(isset($plugs[__FUNCTION__]));
        
        \Plasma\Drivers\MySQL\DriverFactory::addAuthPlugin(__FUNCTION__, $this->getAuthPlugin());
        
        $plugs2 = \Plasma\Drivers\MySQL\DriverFactory::getAuthPlugins();
        $this->assertTrue(isset($plugs2[__FUNCTION__]));
        
        $this->expectException(\Plasma\Exception::class);
        \Plasma\Drivers\MySQL\DriverFactory::addAuthPlugin(__FUNCTION__, $this->getAuthPlugin());
    }
    
    function testAddAuthPluginInterface() {
        $this->expectException(\Plasma\Exception::class);
        \Plasma\Drivers\MySQL\DriverFactory::addAuthPlugin(__FUNCTION__, (new \stdClass()));
    }
    
    function getAuthPlugin() {
        $parser = $this->getMockBuilder(\Plasma\Drivers\MySQL\ProtocolParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $handshake = $this->getMockBuilder(\Plasma\Drivers\MySQL\Messages\HandshakeMessage::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        return (new class($parser, $handshake) implements \Plasma\Drivers\MySQL\AuthPlugins\AuthPluginInterface {
            function __construct(\Plasma\Drivers\MySQL\ProtocolParser $parser, \Plasma\Drivers\MySQL\Messages\HandshakeMessage $handshake) {}
            function getHandshakeAuth(string $password): string {}
            function receiveMoreData(\Plasma\Drivers\MySQL\Messages\AuthMoreDataMessage $message): \Plasma\Drivers\MySQL\Commands\CommandInterface {}
        });
    }
}
