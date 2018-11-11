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
 * The MySQL Command Constants.
 * @internal
 */
class CommandConstants {
    /**
     * mysql_close
     */
    const CMD_QUIT = 0x01;
    
    /**
     * mysql_select_db
     */
    const CMD_INIT_DB = 0x02;
    
    /**
     * mysql_real_query
     */
    const CMD_QUERY = 0x03;
    
    /**
     * mysql_list_fields
     */
    const CMD_FIELD_LIST = 0x04;
    
    /**
     * mysql_create_db (deprecated)
     */
    const CMD_CREATE_DB = 0x05;
    
    /**
     * mysql_drop_db (deprecated)
     */
    const CMD_DROP_DB = 0x06;
    
    /**
     * mysql_refresh
     */
    const CMD_REFRESH = 0x07;
    
    /**
     * mysql_shutdown
     */
    const CMD_SHUTDOWN = 0x08;
    
    /**
     * mysql_stat
     */
    const CMD_STATISTICS = 0x09;
    
    /**
     * mysql_list_processes
     */
    const CMD_PROCESS_INFO = 0x0A;
    
    /**
     * mysql_kill
     */
    const CMD_PROCESS_KILL = 0x0C;
    
    /**
     * mysql_dump_debug_info
     */
    const CMD_DEBUG = 0x0D;
    
    /**
     * mysql_ping
     */
    const CMD_PING = 0x0E;
    
    /**
     * mysql_change_user
     */
    const CMD_CHANGE_USER = 0x11;
    
    /**
     * sent by the slave IO thread to request a binlog
     */
    const CMD_BINLOG_DUMP = 0x12;
    
    /**
     * LOAD TABLE ... FROM MASTER (deprecated)
     */
    const CMD_TABLE_DUMP = 0x13;
    
    /**
     * sent by the slave to register with the master (optional)
     */
    const CMD_REGISTER_SLAVE = 0x15;
    
    /**
     * mysql_stmt_prepare
     */
    const STMT_PREPARE = 0x16;
    
    /**
     * mysql_stmt_execute
     */
    const STMT_EXECUTE = 0x17;
    
    /**
     * mysql_stmt_send_long_data
     */
    const STMT_SEND_LONG_DATA = 0x18;
    
    /**
     * mysql_stmt_close
     */
    const STMT_CLOSE = 0x19;
    
    /**
     * mysql_stmt_reset
     */
    const STMT_RESET = 0x1A;
    
    /**
     * mysql_set_server_option
     */
    const SET_OPTION = 0x1B;
    
    /**
     * mysql_stmt_fetch
     */
    const STMT_FETCH = 0x1C;
}
