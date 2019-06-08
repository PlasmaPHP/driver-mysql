<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL\Tests;

class CommandTest extends TestCase {
    function testEncodingThrowException() {
        $loop = \React\EventLoop\Factory::create();
        $driver = new \Plasma\Drivers\MySQL\Driver($loop, array());
        
        $reflection = new \ReflectionProperty($driver, 'parser');
        $reflection->setAccessible(true);
        $reflection->setValue($driver, (new class() {
            function getHandshakeMessage() {
                $loop = \React\EventLoop\Factory::create();
                $driver = new \Plasma\Drivers\MySQL\Driver($loop, array());
                
                [ $socket ] = \stream_socket_pair((\DIRECTORY_SEPARATOR === '\\' ? \STREAM_PF_INET : \STREAM_PF_UNIX), \STREAM_SOCK_STREAM,  \STREAM_IPPROTO_IP);
                $con = new \React\Socket\Connection($socket, $loop);
                $parser = new \Plasma\Drivers\MySQL\ProtocolParser($driver, $con);
                
                return (new \Plasma\Drivers\MySQL\Messages\HandshakeMessage($parser));
            }
        }));
        
        $command = new \Plasma\Drivers\MySQL\Commands\StatementExecuteCommand($driver, 50, 'SELECT 1', array(array()), array());
        
        $this->expectException(\Plasma\Exception::class);
        $command->getEncodedMessage();
    }
    
    function testThrowExceptionAndLetItBeCaught() {
        $loop = \React\EventLoop\Factory::create();
        $driver = new \Plasma\Drivers\MySQL\Driver($loop, array());
        
        $reflection = new \ReflectionProperty($driver, 'parser');
        $reflection->setAccessible(true);
        $reflection->setValue($driver, (new class() {
            function getHandshakeMessage() {
                $loop = \React\EventLoop\Factory::create();
                $driver = new \Plasma\Drivers\MySQL\Driver($loop, array());
    
                [ $socket ] = \stream_socket_pair((\DIRECTORY_SEPARATOR === '\\' ? \STREAM_PF_INET : \STREAM_PF_UNIX), \STREAM_SOCK_STREAM,  \STREAM_IPPROTO_IP);
                $con = new \React\Socket\Connection($socket, $loop);
                $parser = new \Plasma\Drivers\MySQL\ProtocolParser($driver, $con);
                
                return (new \Plasma\Drivers\MySQL\Messages\HandshakeMessage($parser));
            }
            
            function invokeCommand(\Plasma\CommandInterface $command) {
                $loop = \React\EventLoop\Factory::create();
                $driver = new \Plasma\Drivers\MySQL\Driver($loop, array());
    
                [ $socket ] = \stream_socket_pair((\DIRECTORY_SEPARATOR === '\\' ? \STREAM_PF_INET : \STREAM_PF_UNIX), \STREAM_SOCK_STREAM,  \STREAM_IPPROTO_IP);
                $con = new \React\Socket\Connection($socket, $loop);
                
                $parser = new \Plasma\Drivers\MySQL\ProtocolParser($driver, $con);
                $parser->invokeCommand($command);
            }
        }));
        
        $command = new \Plasma\Drivers\MySQL\Commands\StatementExecuteCommand($driver, 50, 'SELECT 1', array(array()), array());
        $driver->executeCommand($command);
        $promise = $command->getPromise();
        
        $this->expectException(\Plasma\Exception::class);
        $this->await($promise, 0.1);
    }
    
    function createClientMock(): \Plasma\ClientInterface {
        return $this->getMockBuilder(\Plasma\ClientInterface::class)
            ->setMethods(array(
                'create',
                'getConnectionCount',
                'checkinConnection',
                'beginTransaction',
                'close',
                'quit',
                'runCommand',
                'runQuery',
                'query',
                'prepare',
                'execute',
                'quote',
                'listeners',
                'on',
                'once',
                'emit',
                'removeListener',
                'removeAllListeners'
            ))
            ->getMock();
    }
}
