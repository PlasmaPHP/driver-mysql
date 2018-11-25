<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL;

/**
 * The MySQL Capability Flags.
 */
class CapabilityFlags {
    /**
    * New more secure passwords.
    * @var int
    * @source
    */
    const CLIENT_LONG_PASSWORD = 1;
    
    /**
    * Found instead of affected rows.
    * @var int
    * @source
    */
    const CLIENT_FOUND_ROWS = 2;
    
    /**
    * Get all column flags.
    * @var int
    * @source
    */
    const CLIENT_LONG_FLAG = 4;
    
    /**
    * One can specify db on connect.
    * @var int
    * @source
    */
    const CLIENT_CONNECT_WITH_DB = 8;
    
    /**
    * Don't allow database.table.column.
    * @var int
    * @source
    */
    const CLIENT_NO_SCHEMA = 16;
    
    /**
    * Can use compression protocol.
    * @var int
    * @source
    */
    const CLIENT_COMPRESS = 32;
    
    /**
    * ODBC client.
    * @var int
    * @source
    */
    const CLIENT_ODBC = 64;
    
    /**
    * Can use LOAD DATA LOCAL.
    * @var int
    * @source
    */
    const CLIENT_LOCAL_FILES = 128;
    
    /**
    * Ignore spaces before '('.
    * @var int
    * @source
    */
    const CLIENT_IGNORE_SPACE = 256;
    
    /**
    * New MySQL 4.1 protocol.
    * @var int
    * @source
    */
    const CLIENT_PROTOCOL_41 = 512;
    
    /**
    * This is an interactive client.
    * @var int
    * @source
    */
    const CLIENT_INTERACTIVE = 1024;
    
    /**
    * Switch to SSL after handshake.
    * @var int
    * @source
    */
    const CLIENT_SSL = 2048;
    
    /**
    * IGNORE sigpipes.
    * @var int
    * @source
    */
    const CLIENT_IGNORE_SIGPIPE = 4096;
    
    /**
    * Client knows about transactions.
    * @var int
    * @source
    */
    const CLIENT_TRANSACTIONS = 8192;
    
    /**
    * Old flag for 4.1 protocol.
    * @var int
    * @source
    */
    const CLIENT_RESERVED = 16384;
    
    /**
    * New 4.1 authentication.
    * @var int
    * @source
    */
    const CLIENT_SECURE_CONNECTION = 32768;
    
    /**
    * Enable/disable multi-stmt support.
    * @var int
    * @source
    */
    const CLIENT_MULTI_STATEMENTS = 65536;
    
    /**
    * Enable/disable multi-results.
    * @var int
    * @source
    */
    const CLIENT_MULTI_RESULTS = 131072;
    
    /**
    * Client supports plugin authentication (1 << 19).
    * @var int
    * @source
    */
    const CLIENT_PLUGIN_AUTH = 524288;
    
    /**
     * Expects the server to send sesson-state changes.
     * @var int
     * @source
     */
    const CLIENT_SESSION_TRACK = 8388608;
    
    /**
     * Deprecates EOF packet.
     * @var int
     * @source
     */
    const CLIENT_DEPRECATE_EOF = 16777216;
}
