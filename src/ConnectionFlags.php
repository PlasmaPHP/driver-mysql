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
 * The MySQL Connection Flags.
 * @internal
 */
class ConnectionFlags {
    /**
    * new more secure passwords
    */
    const CLIENT_LONG_PASSWORD = 1;
    
    /**
    * Found instead of affected rows
    */
    const CLIENT_FOUND_ROWS = 2;
    
    /**
    * Get all column flags
    */
    const CLIENT_LONG_FLAG = 4;
    
    /**
    * One can specify db on connect
    */
    const CLIENT_CONNECT_WITH_DB = 8;
    
    /**
    * Don't allow database.table.column
    */
    const CLIENT_NO_SCHEMA = 16;
    
    /**
    * Can use compression protocol
    */
    const CLIENT_COMPRESS = 32;
    
    /**
    * Odbc client
    */
    const CLIENT_ODBC = 64;
    
    /**
    * Can use LOAD DATA LOCAL
    */
    const CLIENT_LOCAL_FILES = 128;
    
    /**
    * Ignore spaces before '('
    */
    const CLIENT_IGNORE_SPACE = 256;
    
    /**
    * New 4.1 protocol
    */
    const CLIENT_PROTOCOL_41 = 512;
    
    /**
    * This is an interactive client
    */
    const CLIENT_INTERACTIVE = 1024;
    
    /**
    * Switch to SSL after handshake
    */
    const CLIENT_SSL = 2048;
    
    /**
    * IGNORE sigpipes
    */
    const CLIENT_IGNORE_SIGPIPE = 4096;
    
    /**
    * Client knows about transactions
    */
    const CLIENT_TRANSACTIONS = 8192;
    
    /**
    * Old flag for 4.1 protocol
    */
    const CLIENT_RESERVED = 16384;
    
    /**
    * New 4.1 authentication
    */
    const CLIENT_SECURE_CONNECTION = 32768;
    
    /**
    * Enable/disable multi-stmt support
    */
    const CLIENT_MULTI_STATEMENTS = 65536;
    
    /**
    * Enable/disable multi-results
    */
    const CLIENT_MULTI_RESULTS = 131072;
    
    /**
    * Client supports plugin authentication (1 << 19)
    */
    const CLIENT_PLUGIN_AUTH = 524288;
}
