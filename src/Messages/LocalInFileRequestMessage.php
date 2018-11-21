<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL\Messages;

/**
 * Represents a Local In File Data Message.
 * @internal
 */
class LocalInFileRequestMessage implements \Plasma\Drivers\MySQL\Messages\MessageInterface {
    /**
     * @var \Plasma\Drivers\MySQL\ProtocolParser
     */
    protected $parser;
    
    /**
     * Constructor.
     * @param \Plasma\Drivers\MySQL\ProtocolParser  $parser
     */
    function __construct(\Plasma\Drivers\MySQL\ProtocolParser $parser) {
        $this->parser = $parser;
    }
    
    /**
     * Get the identifier for the packet.
     * @return string
     */
    static function getID(): string {
        return "\xFB";
    }
    
    /**
     * Parses the message, once the complete string has been received.
     * Returns false if not enough data has been received, or the remaining buffer.
     * @param \Plasma\BinaryBuffer  $buffer
     * @return bool
     * @throws \Plasma\Drivers\MySQL\Messages\ParseException
     */
    function parseMessage(\Plasma\BinaryBuffer $buffer): bool {
        $filesystem = \Plasma\Drivers\MySQL\DriverFactory::getFilesystem();
        
        if($filesystem !== null) {
            $filesystem->file($buffer->getContents())->getContents()->then(function (string $content) {
                $this->sendFile($content);
            }, function () {
                $this->parser->sendPacket('');
            });
        } else {
            if(\file_exists($buffer->getContents())) {
                $this->sendFile(\file_get_contents($buffer->getContents()));
            } else {
                $this->parser->sendPacket('');
            }
        }
        
        return true;
    }
    
    /**
     * Get the parser which created this message.
     * @return \Plasma\Drivers\MySQL\ProtocolParser
     */
    function getParser(): \Plasma\Drivers\MySQL\ProtocolParser {
        return $this->parser;
    }
    
    /**
     * Sets the parser state, if necessary. If not, return `-1`.
     * @return int
     */
    function setParserState(): int {
        return -1;
    }
    
    /**
     * Sends the contents to the server.
     * @param string $content
     * @return void
     */
    protected function sendFile(string $content): void {
        $this->parser->sendPacket($content);
        $this->parser->sendPacket('');
    }
}
