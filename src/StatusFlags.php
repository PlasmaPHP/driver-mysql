<?php
/**
 * Plasma Driver MySQL component
 * Copyright 2018-2019 PlasmaPHP, All Rights Reserved
 *
 * Website: https://github.com/PlasmaPHP
 * License: https://github.com/PlasmaPHP/driver-mysql/blob/master/LICENSE
*/

namespace Plasma\Drivers\MySQL;

/**
 * The MySQL Status Flags.
 */
class StatusFlags {
    /**
    * We are in a transaction.
    * @var int
    * @source
    */
    const SERVER_STATUS_IN_TRANS = 0x0001;
    
    /**
    * Auto-commit is enabled.
    * @var int
    * @source
    */
    const SERVER_STATUS_AUTOCOMMIT = 0x0002;
    
    /**
    * There are more results - amazing!
    * @var int
    * @source
    */
    const SERVER_MORE_RESULTS_EXISTS = 0x0008;
    
    /**
    * The database was unable to use a performant index.
    * @var int
    * @source
    */
    const SERVER_STATUS_NO_GOOD_INDEX_USED = 0x0010;
    
    /**
    * The database was unable to use a index.
    * @var int
    * @source
    */
    const SERVER_STATUS_NO_INDEX_USED = 0x0020;
    
    /**
    * Used by Binary Protocol Resultset to signal that COM_STMT_FETCH must be used to fetch the row-data.
    * @var int
    * @source
    */
    const SERVER_STATUS_CURSOR_EXISTS = 0x0040;
    
    /**
    * The last row was sent.
    * @var int
    * @source
    */
    const SERVER_STATUS_LAST_ROW_SENT = 0x0080;
    
    /**
    * A database was dropped.
    * @var int
    * @source
    */
    const SERVER_STATUS_DB_DROPPED = 0x0100;
    
    /**
    * Backslashes are not allowed for escaping.
    * @var int
    * @source
    */
    const SERVER_STATUS_NO_BACKSLASH_ESCAPES = 0x0200;
    
    /**
    * The metadata have changed.
    * @var int
    * @source
    */
    const SERVER_STATUS_METADATA_CHANGED = 0x0400;
    
    /**
    * The query was rated slow.
    * @var int
    * @source
    */
    const SERVER_QUERY_WAS_SLOW = 0x0800;
    
    /**
    * PS out params - apparently.
    * @var int
    * @source
    */
    const SERVER_PS_OUT_PARAMS = 0x1000;
    
    /**
    * In a read-only transaction.
    * @var int
    * @source
    */
    const SERVER_STATUS_IN_TRANS_READONLY = 0x2000;
    
    /**
    * Connection state information has changed.
    * @var int
    * @source
    */
    const SERVER_SESSION_STATE_CHANGED = 0x4000;
}
