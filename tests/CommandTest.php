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

use Plasma\ClientInterface;
use Plasma\CommandInterface;
use Plasma\Drivers\MySQL\Commands\StatementExecuteCommand;
use Plasma\Drivers\MySQL\Driver;
use Plasma\Drivers\MySQL\Messages\HandshakeMessage;
use Plasma\Drivers\MySQL\ProtocolParser;
use Plasma\Exception;
use React\EventLoop\Factory;
use React\Socket\Connection;

class CommandTest extends TestCase {
    function testEncodingThrowException() {
        $loop = Factory::create();
        $driver = new Driver($loop, array());
        
        $reflection = new \ReflectionProperty($driver, 'parser');
        $reflection->setAccessible(true);
        $reflection->setValue($driver, (new class() {
            function getHandshakeMessage() {
                $loop = Factory::create();
                $driver = new Driver($loop, array());
                
                [ $socket ] = \stream_socket_pair(
                    (\DIRECTORY_SEPARATOR === '\\' ? \STREAM_PF_INET : \STREAM_PF_UNIX),
                    \STREAM_SOCK_STREAM,
                    \STREAM_IPPROTO_IP
                );
                $con = new Connection($socket, $loop);
                $parser = new ProtocolParser($driver, $con);
                
                return (new HandshakeMessage($parser));
            }
        }));
        
        $command = new StatementExecuteCommand($driver, 50, 'SELECT 1', array(array()), array());
        
        $this->expectException(Exception::class);
        $command->getEncodedMessage();
    }
    
    function testThrowExceptionAndLetItBeCaught() {
        $loop = Factory::create();
        $driver = new Driver($loop, array());
        
        $reflection = new \ReflectionProperty($driver, 'parser');
        $reflection->setAccessible(true);
        $reflection->setValue($driver, (new class() {
            function getHandshakeMessage() {
                $loop = Factory::create();
                $driver = new Driver($loop, array());
    
                [ $socket ] = \stream_socket_pair((\DIRECTORY_SEPARATOR === '\\' ? \STREAM_PF_INET : \STREAM_PF_UNIX), \STREAM_SOCK_STREAM,  \STREAM_IPPROTO_IP);
                $con = new Connection($socket, $loop);
                $parser = new ProtocolParser($driver, $con);
                
                return (new HandshakeMessage($parser));
            }
            
            function invokeCommand(CommandInterface $command) {
                $loop = Factory::create();
                $driver = new Driver($loop, array());
    
                [ $socket ] = \stream_socket_pair((\DIRECTORY_SEPARATOR === '\\' ? \STREAM_PF_INET : \STREAM_PF_UNIX), \STREAM_SOCK_STREAM,  \STREAM_IPPROTO_IP);
                $con = new Connection($socket, $loop);
                
                $parser = new ProtocolParser($driver, $con);
                $parser->invokeCommand($command);
            }
        }));
        
        $command = new StatementExecuteCommand($driver, 50, 'SELECT 1', array(array()), array());
        $driver->executeCommand($command);
        $promise = $command->getPromise();
        
        $this->expectException(Exception::class);
        $this->await($promise, 0.1);
    }
    
    function createClientMock(): ClientInterface {
        return $this->getMockBuilder(ClientInterface::class)
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
