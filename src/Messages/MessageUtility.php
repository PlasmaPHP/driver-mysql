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
 * Utilities for messages.
 * @internal
 */
class MessageUtility {
    /**
     * Parses a 1 byte / 8 bit integer (0 to 255).
     * @return int
     */
    static function readInt1(string &$buffer): int {
        return \ord(static::readBuffer($buffer, 1));
    }
    
    /**
     * Parses a 2 byte / 16 bit integer (0 to 64 K / 0xFFFF).
     * @return int
     */
    static function readInt2(string &$buffer): int {
        return \unpack('v', static::readBuffer($buffer, 2))[1];
    }
    
    /**
     * Parses a 3 byte / 24 bit integer (0 to 16 M / 0xFFFFFF).
     * @return int
     */
    static function readInt3(string &$buffer): int {
        return \unpack('V', static::readBuffer($buffer, 3)."\0")[1];
    }
    
    /**
     * Parses a 4 byte / 32 bit integer (0 to 4 G / 0xFFFFFFFF).
     * @return int
     */
    static function readInt4(string &$buffer): int {
        return \unpack('V', static::readBuffer($buffer, 4))[1];
    }
    
    /**
     * Parses a 8 byte / 64 bit integer (0 to 2^64-1).
     * @return int|string
     */
    static function readInt8(string &$buffer) {
        $strInt = static::readBuffer($buffer, 8);
        
        if(\PHP_INT_SIZE > 4) {
            return \unpack('P', $strInt)[1];
        }
        
        $result = \bcadd('0', \unpack('n', \substr($strInt, 0, 2)));
        $result = \bcmul($result, '65536');
        $result = \bcadd($result, \unpack('n', \substr($strInt, 2, 2)));
        $result = \bcmul($result, '65536');
        $result = \bcadd($result, \unpack('n', \substr($strInt, 4, 2)));
        $result = \bcmul($result, '65536');
        $result = \bcadd($result, \unpack('n', \substr($strInt, 6, 2)));
        
        // 9223372036854775808 is equal to (1 << 63)
        if(\bccomp($result, '9223372036854775808') !== -1) {
            $result = \bcsub($result, '18446744073709551616'); // $result -= (1 << 64)
        }
        
        return $result;
    }
    
    /**
     * Parses length-encoded binary integer.
     * Returns the decoded integer 0 to 2^64 or `null` for special null int.
     * @return int|null
     */
    static function readIntLength(string &$buffer): ?int {
        $f = static::readInt1($buffer);
        if($f <= 250) {
            return $f;
        }
        
        if($f === 251) {
            return null;
        }
        
        if($f === 252) {
            return static::readInt2($buffer);
        }
        
        if($f === 253) {
            return static::readInt3($buffer);
        }
        
        return static::readInt8($buffer);
    }
    
    /**
     * Parses a length-encoded binary string. If length is null, `null` will be returned.
     * @return string|null
     */
    static function readStringLength(string &$buffer, ?int $length = null): ?string {
        $length = ($length !== null ? $length : static::readIntLength($buffer));
        if($length === null) {
            return null;
        }
        
        return static::readBuffer($buffer, $length);
    }
    
    /**
     * Reads NULL-terminated C string.
     * @return string
     * @throws \InvalidArgumentException
     */
    static function readStringNull(string &$buffer): string {
        $pos = \strpos($buffer, "\0");
        if($pos === false) {
            throw new \InvalidArgumentException('Missing NULL character');
        }
        
        $str =  static::readBuffer($buffer, $pos);
        static::readBuffer($buffer, 1); // discard NULL byte
        
        return $str;
    }
    
    /**
     * @param int $int
     * @return string
     */
    static function writeInt1(int $int): string {
        return \chr($int);
    }
    
    /**
     * @param int $int
     * @return string
     */
    static function writeInt2(int $int): string {
        return \pack('v', $int);
    }
    
    /**
     * @param int $int
     * @return string
     */
    static function writeInt3(int $int): string {
        return \substr(\pack('V', $int), 0, 3);
    }
    
    /**
     * @param int $int
     * @return string
     */
    static function writeInt4(int $int): string {
        return \pack('V', $int);
    }
    
    /**
     * @param string|int $int
     * @return string
     */
    static function writeInt8($int): string {
        if(\PHP_INT_SIZE > 4) {
            return \pack('P', ((int) $int));
        }
        
        if(\bccomp($int, '0') === -1) {
            // 18446744073709551616 is equal to (1 << 64)
            $int = \bcadd($int, '18446744073709551616');
        }
        
        return \pack('v', \bcmod(\bcdiv($int, '281474976710656'), '65536')).
            \pack('v', \bcmod(\bcdiv($int, '4294967296'), '65536')).
            \pack('v', \bcdiv($int, '65536'), '65536').
            \pack('v', \bcmod($int, '65536'));
    }
    
    /**
     * @param float  $float
     * @return string
     */
    static function writeFloat(float $float): string {
        return \pack('e', $float);
    }
    
    /**
     * Builds length-encoded binary string.
     * @param string|null $s
     * @return string
     */
    static function writeStringLength(?string $s): string {
        if($s === NULL) {
            // \xFB (251)
            return "\xFB";
        }
        
        $l = \strlen($s);
        if($l <= 250) {
            return static::writeInt1($l).$s;
        }
        
        if($l <= 0xFFFF) { // max 2^16: \xFC (252)
            return "\xFC".static::writeInt2($l).$s;
        }
        
        if($l <= 0xFFFFFF) { // max 2^24: \xFD (253)
            return "\xFD".static::writeInt3($l).$s;
        }
        
        return "\xFE".static::writeInt8($l).$s; // max 2^64: \xFE (254)
    }
    
    /**
     * Reads a specified length from the buffer and discards the read part.
     * @return string
     * @throws \InvalidArgumentException
     */
    static function readBuffer(string &$buffer, int $length): string {
        if(\strlen($buffer) < $length) {
            throw new \InvalidArgumentException('Trying to read behind buffer');
        }
        
        $str = \substr($buffer, 0, $length);
        $buffer = \substr($buffer, $length);
        
        return $str;
    }
}
