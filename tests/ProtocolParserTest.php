<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL\Tests;

class ProtolParserTest extends TestCase {
    function testMaxPacketSize() {
        $driver = $this->getMockBuilder(\Plasma\Drivers\MySQL\Driver::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $connection = $this->getMockBuilder(\React\Socket\ConnectionInterface::class)
            ->setMethods(array(
                'getRemoteAddress',
                'getLocalAddress',
                'isReadable',
                'isWritable',
                'pause',
                'resume',
                'pipe',
                'close',
                'on',
                'once',
                'removeListener',
                'removeAllListeners',
                'listeners',
                'emit',
                'write',
                'end'
            ))
            ->disableOriginalConstructor()
            ->getMock();
        
        $parser = new \Plasma\Drivers\MySQL\ProtocolParser($driver, $connection);
        
        $max = \Plasma\Drivers\MySQL\ProtocolParser::CLIENT_MAX_PACKET_SIZE;
        $data = \str_repeat('0', $max);
        
        $connection
            ->expects($this->at(0))
            ->method('write')
            ->with(\Plasma\BinaryBuffer::writeInt3($max).\Plasma\BinaryBuffer::writeInt1(1).$data);
        
        $connection
            ->expects($this->at(1))
            ->method('write')
            ->with(\Plasma\BinaryBuffer::writeInt3(0).\Plasma\BinaryBuffer::writeInt1(2));
        
        $parser->sendPacket($data);
    }
}
