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

use Plasma\BinaryBuffer;
use Plasma\Drivers\MySQL\Driver;
use Plasma\Drivers\MySQL\ProtocolParser;
use React\Socket\ConnectionInterface;

class ProtocolParserTest extends TestCase {
    function testMaxPacketSize() {
        $driver = $this->getMockBuilder(Driver::class)
            ->disableOriginalConstructor()
            ->getMock();
        
        $connection = $this->getMockBuilder(ConnectionInterface::class)
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
        
        $parser = new ProtocolParser($driver, $connection);
        
        $max = ProtocolParser::CLIENT_MAX_PACKET_SIZE;
        $data = \str_repeat('0', $max);
        
        $connection
            ->expects(self::at(0))
            ->method('write')
            ->with(BinaryBuffer::writeInt3($max).BinaryBuffer::writeInt1(0).$data);
        
        $connection
            ->expects(self::at(1))
            ->method('write')
            ->with(BinaryBuffer::writeInt3(0).BinaryBuffer::writeInt1(1));
        
        $parser->sendPacket($data);
    }
}
