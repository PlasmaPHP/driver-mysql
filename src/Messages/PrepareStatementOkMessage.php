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
 * Represents a Prepare Statement Ok Message.
 * @internal
 */
class PrepareStatementOkMessage implements \Plasma\Drivers\MySQL\Messages\MessageInterface {
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
        return "\x00";
    }
    
    /**
     * Parses the message, once the complete string has been received.
     * Returns false if not enough data has been received, or the remaining buffer.
     * @param string  $buffer
     * @return string|bool
     * @throws \Plasma\Drivers\MySQL\Messages\ParseException
     */
    function parseMessage(string $buffer) {
        if(\strlen($buffer) < 12) {
            return false;
        }
        
        $statementID = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt4($buffer);
        $numColumns = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt2($buffer);
        $numParams = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt2($buffer);
        
        $buffer = \substr($buffer, 1); // Filler
        
        $warningsCount = \Plasma\Drivers\MySQL\Messages\MessageUtility::readInt2($buffer);
        
        $this->statementID = $statementID;
        $this->numColumns = $numColumns;
        $this->numParams = $numParams;
        $this->warningsCount = $warningsCount;
        
        return $buffer;
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
        return \Plasma\Drivers\MySQL\ProtocolParser::STATE_OK;
    }
}
