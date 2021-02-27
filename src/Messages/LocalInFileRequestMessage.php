<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
 */

namespace Plasma\Drivers\MySQL\Messages;

use Plasma\BinaryBuffer;
use Plasma\Drivers\MySQL\DriverFactory;
use Plasma\Drivers\MySQL\ProtocolParser;

/**
 * Represents a Local In File Data Message.
 */
class LocalInFileRequestMessage implements MessageInterface {
    /**
     * @var ProtocolParser
     */
    protected $parser;
    
    /**
     * Constructor.
     * @param ProtocolParser  $parser
     * @internal
     */
    function __construct(ProtocolParser $parser) {
        $this->parser = $parser;
    }
    
    /**
     * Get the identifier for the packet.
     * @return string
     * @internal
     */
    static function getID(): string {
        return "\xFB";
    }
    
    /**
     * Parses the message, once the complete string has been received.
     * Returns false if not enough data has been received, or the remaining buffer.
     * @param BinaryBuffer  $buffer
     * @return bool
     * @internal
     */
    function parseMessage(BinaryBuffer $buffer): bool {
        $filesystem = DriverFactory::getFilesystem();
        
        if($filesystem !== null) {
            $filesystem->file($buffer->getContents())->getContents()->then(
                function (string $content) {
                    $this->sendFile($content);
                },
                function () {
                    $this->parser->sendPacket('');
                }
            );
        } elseif(\file_exists($buffer->getContents())) {
            $this->sendFile(\file_get_contents($buffer->getContents()));
        } else {
            $this->parser->sendPacket('');
        }
        
        return true;
    }
    
    /**
     * Get the parser which created this message.
     * @return ProtocolParser
     * @internal
     */
    function getParser(): ProtocolParser {
        return $this->parser;
    }
    
    /**
     * Sets the parser state, if necessary. If not, return `-1`.
     * @return int
     * @internal
     */
    function setParserState(): int {
        return -1;
    }
    
    /**
     * Sends the contents to the server.
     * @param string  $content
     * @return void
     */
    protected function sendFile(string $content): void {
        $this->parser->sendPacket($content);
        $this->parser->sendPacket('');
    }
}
