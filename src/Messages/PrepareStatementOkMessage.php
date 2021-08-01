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
use Plasma\Drivers\MySQL\ProtocolParser;

/**
 * Represents a Prepare Statement Ok Message.
 */
class PrepareStatementOkMessage implements MessageInterface {
    /**
     * The statement ID.
     * @var int
     */
    public $statementID;
    
    /**
     * Count of columns.
     * @var int
     */
    public $numColumns;
    
    /**
     * Count of parameters.
     * @var int
     */
    public $numParams;
    
    /**
     * Count of warnings.
     * @var int
     */
    public $warningsCount;
    
    /**
     * @var ProtocolParser
     * @internal
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
        return "\x00";
    }
    
    /**
     * Parses the message, once the complete string has been received.
     * Returns false if not enough data has been received, or the remaining buffer.
     * @param BinaryBuffer  $buffer
     * @return bool
     * @internal
     */
    function parseMessage(BinaryBuffer $buffer): bool {
        if($buffer->getSize() < 11) {
            return false;
        }
        
        $statementID = $buffer->readInt4();
        $numColumns = $buffer->readInt2();
        $numParams = $buffer->readInt2();
        
        $buffer->read(1); // Filler
        
        $warningsCount = $buffer->readInt2();
        
        $this->statementID = $statementID;
        $this->numColumns = $numColumns;
        $this->numParams = $numParams;
        $this->warningsCount = $warningsCount;
        
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
        return ProtocolParser::STATE_OK;
    }
}
