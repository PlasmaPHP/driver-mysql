
window.projectVersion = 'master';

(function(root) {

    var bhIndex = null;
    var rootPath = '';
    var treeHtml = '        <ul>                <li data-name="namespace:Plasma" class="opened">                    <div style="padding-left:0px" class="hd">                        <span class="glyphicon glyphicon-play"></span><a href="Plasma.html">Plasma</a>                    </div>                    <div class="bd">                                <ul>                <li data-name="namespace:Plasma_Drivers" class="opened">                    <div style="padding-left:18px" class="hd">                        <span class="glyphicon glyphicon-play"></span><a href="Plasma/Drivers.html">Drivers</a>                    </div>                    <div class="bd">                                <ul>                <li data-name="namespace:Plasma_Drivers_MySQL" >                    <div style="padding-left:36px" class="hd">                        <span class="glyphicon glyphicon-play"></span><a href="Plasma/Drivers/MySQL.html">MySQL</a>                    </div>                    <div class="bd">                                <ul>                <li data-name="namespace:Plasma_Drivers_MySQL_Messages" >                    <div style="padding-left:54px" class="hd">                        <span class="glyphicon glyphicon-play"></span><a href="Plasma/Drivers/MySQL/Messages.html">Messages</a>                    </div>                    <div class="bd">                                <ul>                <li data-name="class:Plasma_Drivers_MySQL_Messages_AuthMoreDataMessage" >                    <div style="padding-left:80px" class="hd leaf">                        <a href="Plasma/Drivers/MySQL/Messages/AuthMoreDataMessage.html">AuthMoreDataMessage</a>                    </div>                </li>                            <li data-name="class:Plasma_Drivers_MySQL_Messages_AuthSwitchRequestMessage" >                    <div style="padding-left:80px" class="hd leaf">                        <a href="Plasma/Drivers/MySQL/Messages/AuthSwitchRequestMessage.html">AuthSwitchRequestMessage</a>                    </div>                </li>                            <li data-name="class:Plasma_Drivers_MySQL_Messages_EOFMessage" >                    <div style="padding-left:80px" class="hd leaf">                        <a href="Plasma/Drivers/MySQL/Messages/EOFMessage.html">EOFMessage</a>                    </div>                </li>                            <li data-name="class:Plasma_Drivers_MySQL_Messages_ErrResponseMessage" >                    <div style="padding-left:80px" class="hd leaf">                        <a href="Plasma/Drivers/MySQL/Messages/ErrResponseMessage.html">ErrResponseMessage</a>                    </div>                </li>                            <li data-name="class:Plasma_Drivers_MySQL_Messages_HandshakeMessage" >                    <div style="padding-left:80px" class="hd leaf">                        <a href="Plasma/Drivers/MySQL/Messages/HandshakeMessage.html">HandshakeMessage</a>                    </div>                </li>                            <li data-name="class:Plasma_Drivers_MySQL_Messages_LocalInFileRequestMessage" >                    <div style="padding-left:80px" class="hd leaf">                        <a href="Plasma/Drivers/MySQL/Messages/LocalInFileRequestMessage.html">LocalInFileRequestMessage</a>                    </div>                </li>                            <li data-name="class:Plasma_Drivers_MySQL_Messages_MessageInterface" >                    <div style="padding-left:80px" class="hd leaf">                        <a href="Plasma/Drivers/MySQL/Messages/MessageInterface.html">MessageInterface</a>                    </div>                </li>                            <li data-name="class:Plasma_Drivers_MySQL_Messages_OkResponseMessage" >                    <div style="padding-left:80px" class="hd leaf">                        <a href="Plasma/Drivers/MySQL/Messages/OkResponseMessage.html">OkResponseMessage</a>                    </div>                </li>                            <li data-name="class:Plasma_Drivers_MySQL_Messages_ParseException" >                    <div style="padding-left:80px" class="hd leaf">                        <a href="Plasma/Drivers/MySQL/Messages/ParseException.html">ParseException</a>                    </div>                </li>                            <li data-name="class:Plasma_Drivers_MySQL_Messages_PrepareStatementOkMessage" >                    <div style="padding-left:80px" class="hd leaf">                        <a href="Plasma/Drivers/MySQL/Messages/PrepareStatementOkMessage.html">PrepareStatementOkMessage</a>                    </div>                </li>                </ul></div>                </li>                            <li data-name="class:Plasma_Drivers_MySQL_CapabilityFlags" >                    <div style="padding-left:62px" class="hd leaf">                        <a href="Plasma/Drivers/MySQL/CapabilityFlags.html">CapabilityFlags</a>                    </div>                </li>                            <li data-name="class:Plasma_Drivers_MySQL_CharacterSetFlags" >                    <div style="padding-left:62px" class="hd leaf">                        <a href="Plasma/Drivers/MySQL/CharacterSetFlags.html">CharacterSetFlags</a>                    </div>                </li>                            <li data-name="class:Plasma_Drivers_MySQL_ColumnDefinition" >                    <div style="padding-left:62px" class="hd leaf">                        <a href="Plasma/Drivers/MySQL/ColumnDefinition.html">ColumnDefinition</a>                    </div>                </li>                            <li data-name="class:Plasma_Drivers_MySQL_DriverFactory" >                    <div style="padding-left:62px" class="hd leaf">                        <a href="Plasma/Drivers/MySQL/DriverFactory.html">DriverFactory</a>                    </div>                </li>                            <li data-name="class:Plasma_Drivers_MySQL_FieldFlags" >                    <div style="padding-left:62px" class="hd leaf">                        <a href="Plasma/Drivers/MySQL/FieldFlags.html">FieldFlags</a>                    </div>                </li>                            <li data-name="class:Plasma_Drivers_MySQL_Statement" >                    <div style="padding-left:62px" class="hd leaf">                        <a href="Plasma/Drivers/MySQL/Statement.html">Statement</a>                    </div>                </li>                            <li data-name="class:Plasma_Drivers_MySQL_StatementCursor" >                    <div style="padding-left:62px" class="hd leaf">                        <a href="Plasma/Drivers/MySQL/StatementCursor.html">StatementCursor</a>                    </div>                </li>                            <li data-name="class:Plasma_Drivers_MySQL_StatusFlags" >                    <div style="padding-left:62px" class="hd leaf">                        <a href="Plasma/Drivers/MySQL/StatusFlags.html">StatusFlags</a>                    </div>                </li>                </ul></div>                </li>                </ul></div>                </li>                </ul></div>                </li>                </ul>';

    var searchTypeClasses = {
        'Namespace': 'label-default',
        'Class': 'label-info',
        'Interface': 'label-primary',
        'Trait': 'label-success',
        'Method': 'label-danger',
        '_': 'label-warning'
    };

    var searchIndex = [
                    
            {"type": "Namespace", "link": "Plasma.html", "name": "Plasma", "doc": "Namespace Plasma"},{"type": "Namespace", "link": "Plasma/Drivers.html", "name": "Plasma\\Drivers", "doc": "Namespace Plasma\\Drivers"},{"type": "Namespace", "link": "Plasma/Drivers/MySQL.html", "name": "Plasma\\Drivers\\MySQL", "doc": "Namespace Plasma\\Drivers\\MySQL"},{"type": "Namespace", "link": "Plasma/Drivers/MySQL/Messages.html", "name": "Plasma\\Drivers\\MySQL\\Messages", "doc": "Namespace Plasma\\Drivers\\MySQL\\Messages"},
            {"type": "Interface", "fromName": "Plasma\\Drivers\\MySQL\\Messages", "fromLink": "Plasma/Drivers/MySQL/Messages.html", "link": "Plasma/Drivers/MySQL/Messages/MessageInterface.html", "name": "Plasma\\Drivers\\MySQL\\Messages\\MessageInterface", "doc": "&quot;Represents an incoming message.&quot;"},
                                                        {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\Messages\\MessageInterface", "fromLink": "Plasma/Drivers/MySQL/Messages/MessageInterface.html", "link": "Plasma/Drivers/MySQL/Messages/MessageInterface.html#method___construct", "name": "Plasma\\Drivers\\MySQL\\Messages\\MessageInterface::__construct", "doc": "&quot;Constructor.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\Messages\\MessageInterface", "fromLink": "Plasma/Drivers/MySQL/Messages/MessageInterface.html", "link": "Plasma/Drivers/MySQL/Messages/MessageInterface.html#method_getID", "name": "Plasma\\Drivers\\MySQL\\Messages\\MessageInterface::getID", "doc": "&quot;Get the identifier for the packet.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\Messages\\MessageInterface", "fromLink": "Plasma/Drivers/MySQL/Messages/MessageInterface.html", "link": "Plasma/Drivers/MySQL/Messages/MessageInterface.html#method_parseMessage", "name": "Plasma\\Drivers\\MySQL\\Messages\\MessageInterface::parseMessage", "doc": "&quot;Parses the message, once the complete string has been received.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\Messages\\MessageInterface", "fromLink": "Plasma/Drivers/MySQL/Messages/MessageInterface.html", "link": "Plasma/Drivers/MySQL/Messages/MessageInterface.html#method_getParser", "name": "Plasma\\Drivers\\MySQL\\Messages\\MessageInterface::getParser", "doc": "&quot;Get the parser which created this message.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\Messages\\MessageInterface", "fromLink": "Plasma/Drivers/MySQL/Messages/MessageInterface.html", "link": "Plasma/Drivers/MySQL/Messages/MessageInterface.html#method_setParserState", "name": "Plasma\\Drivers\\MySQL\\Messages\\MessageInterface::setParserState", "doc": "&quot;Sets the parser state, if necessary. If not, return &lt;code&gt;-1&lt;\/code&gt;.&quot;"},
            
            
            {"type": "Class", "fromName": "Plasma\\Drivers\\MySQL", "fromLink": "Plasma/Drivers/MySQL.html", "link": "Plasma/Drivers/MySQL/CapabilityFlags.html", "name": "Plasma\\Drivers\\MySQL\\CapabilityFlags", "doc": "&quot;The MySQL Capability Flags.&quot;"},
                    {"type": "Class", "fromName": "Plasma\\Drivers\\MySQL", "fromLink": "Plasma/Drivers/MySQL.html", "link": "Plasma/Drivers/MySQL/CharacterSetFlags.html", "name": "Plasma\\Drivers\\MySQL\\CharacterSetFlags", "doc": "&quot;The MySQL character set flags.&quot;"},
                    {"type": "Class", "fromName": "Plasma\\Drivers\\MySQL", "fromLink": "Plasma/Drivers/MySQL.html", "link": "Plasma/Drivers/MySQL/ColumnDefinition.html", "name": "Plasma\\Drivers\\MySQL\\ColumnDefinition", "doc": "&quot;Column Definitions define columns (who would&#039;ve thought of that?). Such as their name, type, length, etc.&quot;"},
                                                        {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\ColumnDefinition", "fromLink": "Plasma/Drivers/MySQL/ColumnDefinition.html", "link": "Plasma/Drivers/MySQL/ColumnDefinition.html#method_isNullable", "name": "Plasma\\Drivers\\MySQL\\ColumnDefinition::isNullable", "doc": "&quot;Whether the column is nullable (not &lt;code&gt;NOT NULL&lt;\/code&gt;).&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\ColumnDefinition", "fromLink": "Plasma/Drivers/MySQL/ColumnDefinition.html", "link": "Plasma/Drivers/MySQL/ColumnDefinition.html#method_isAutoIncrement", "name": "Plasma\\Drivers\\MySQL\\ColumnDefinition::isAutoIncrement", "doc": "&quot;Whether the column is auto incremented.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\ColumnDefinition", "fromLink": "Plasma/Drivers/MySQL/ColumnDefinition.html", "link": "Plasma/Drivers/MySQL/ColumnDefinition.html#method_isPrimaryKey", "name": "Plasma\\Drivers\\MySQL\\ColumnDefinition::isPrimaryKey", "doc": "&quot;Whether the column is the primary key.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\ColumnDefinition", "fromLink": "Plasma/Drivers/MySQL/ColumnDefinition.html", "link": "Plasma/Drivers/MySQL/ColumnDefinition.html#method_isUniqueKey", "name": "Plasma\\Drivers\\MySQL\\ColumnDefinition::isUniqueKey", "doc": "&quot;Whether the column is the unique key.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\ColumnDefinition", "fromLink": "Plasma/Drivers/MySQL/ColumnDefinition.html", "link": "Plasma/Drivers/MySQL/ColumnDefinition.html#method_isMultipleKey", "name": "Plasma\\Drivers\\MySQL\\ColumnDefinition::isMultipleKey", "doc": "&quot;Whether the column is part of a multiple\/composite key.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\ColumnDefinition", "fromLink": "Plasma/Drivers/MySQL/ColumnDefinition.html", "link": "Plasma/Drivers/MySQL/ColumnDefinition.html#method_isUnsigned", "name": "Plasma\\Drivers\\MySQL\\ColumnDefinition::isUnsigned", "doc": "&quot;Whether the column is unsigned (only makes sense for numeric types).&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\ColumnDefinition", "fromLink": "Plasma/Drivers/MySQL/ColumnDefinition.html", "link": "Plasma/Drivers/MySQL/ColumnDefinition.html#method_isZerofilled", "name": "Plasma\\Drivers\\MySQL\\ColumnDefinition::isZerofilled", "doc": "&quot;Whether the column gets zerofilled to the length.&quot;"},
            {"type": "Class", "fromName": "Plasma\\Drivers\\MySQL", "fromLink": "Plasma/Drivers/MySQL.html", "link": "Plasma/Drivers/MySQL/DriverFactory.html", "name": "Plasma\\Drivers\\MySQL\\DriverFactory", "doc": "&quot;The Driver Factory is responsible for creating the driver correctly.&quot;"},
                                                        {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\DriverFactory", "fromLink": "Plasma/Drivers/MySQL/DriverFactory.html", "link": "Plasma/Drivers/MySQL/DriverFactory.html#method___construct", "name": "Plasma\\Drivers\\MySQL\\DriverFactory::__construct", "doc": "&quot;Constructor.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\DriverFactory", "fromLink": "Plasma/Drivers/MySQL/DriverFactory.html", "link": "Plasma/Drivers/MySQL/DriverFactory.html#method_createDriver", "name": "Plasma\\Drivers\\MySQL\\DriverFactory::createDriver", "doc": "&quot;Creates a new driver instance.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\DriverFactory", "fromLink": "Plasma/Drivers/MySQL/DriverFactory.html", "link": "Plasma/Drivers/MySQL/DriverFactory.html#method_addAuthPlugin", "name": "Plasma\\Drivers\\MySQL\\DriverFactory::addAuthPlugin", "doc": "&quot;Adds an auth plugin. &lt;code&gt;$condition&lt;\/code&gt; is either an int (for server capabilities), or a string (for auth plugin name).&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\DriverFactory", "fromLink": "Plasma/Drivers/MySQL/DriverFactory.html", "link": "Plasma/Drivers/MySQL/DriverFactory.html#method_getAuthPlugins", "name": "Plasma\\Drivers\\MySQL\\DriverFactory::getAuthPlugins", "doc": "&quot;Get the registered auth plugins.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\DriverFactory", "fromLink": "Plasma/Drivers/MySQL/DriverFactory.html", "link": "Plasma/Drivers/MySQL/DriverFactory.html#method_setFilesystem", "name": "Plasma\\Drivers\\MySQL\\DriverFactory::setFilesystem", "doc": "&quot;Set the React Filesystem to use.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\DriverFactory", "fromLink": "Plasma/Drivers/MySQL/DriverFactory.html", "link": "Plasma/Drivers/MySQL/DriverFactory.html#method_getFilesystem", "name": "Plasma\\Drivers\\MySQL\\DriverFactory::getFilesystem", "doc": "&quot;Get the React Filesystem, or null. The filesystem must be set by the user, in order to not get &lt;code&gt;null&lt;\/code&gt;.&quot;"},
            {"type": "Class", "fromName": "Plasma\\Drivers\\MySQL", "fromLink": "Plasma/Drivers/MySQL.html", "link": "Plasma/Drivers/MySQL/FieldFlags.html", "name": "Plasma\\Drivers\\MySQL\\FieldFlags", "doc": "&quot;The MySQL Field Flags.&quot;"},
                    {"type": "Class", "fromName": "Plasma\\Drivers\\MySQL\\Messages", "fromLink": "Plasma/Drivers/MySQL/Messages.html", "link": "Plasma/Drivers/MySQL/Messages/AuthMoreDataMessage.html", "name": "Plasma\\Drivers\\MySQL\\Messages\\AuthMoreDataMessage", "doc": "&quot;Represents an Auth More Data Message.&quot;"},
                    {"type": "Class", "fromName": "Plasma\\Drivers\\MySQL\\Messages", "fromLink": "Plasma/Drivers/MySQL/Messages.html", "link": "Plasma/Drivers/MySQL/Messages/AuthSwitchRequestMessage.html", "name": "Plasma\\Drivers\\MySQL\\Messages\\AuthSwitchRequestMessage", "doc": "&quot;Represents an Auth Switch Request Message.&quot;"},
                    {"type": "Class", "fromName": "Plasma\\Drivers\\MySQL\\Messages", "fromLink": "Plasma/Drivers/MySQL/Messages.html", "link": "Plasma/Drivers/MySQL/Messages/EOFMessage.html", "name": "Plasma\\Drivers\\MySQL\\Messages\\EOFMessage", "doc": "&quot;Represents an EOF Message.&quot;"},
                    {"type": "Class", "fromName": "Plasma\\Drivers\\MySQL\\Messages", "fromLink": "Plasma/Drivers/MySQL/Messages.html", "link": "Plasma/Drivers/MySQL/Messages/ErrResponseMessage.html", "name": "Plasma\\Drivers\\MySQL\\Messages\\ErrResponseMessage", "doc": "&quot;Represents an Error Response Message.&quot;"},
                    {"type": "Class", "fromName": "Plasma\\Drivers\\MySQL\\Messages", "fromLink": "Plasma/Drivers/MySQL/Messages.html", "link": "Plasma/Drivers/MySQL/Messages/HandshakeMessage.html", "name": "Plasma\\Drivers\\MySQL\\Messages\\HandshakeMessage", "doc": "&quot;Represents a Handshake Message.&quot;"},
                    {"type": "Class", "fromName": "Plasma\\Drivers\\MySQL\\Messages", "fromLink": "Plasma/Drivers/MySQL/Messages.html", "link": "Plasma/Drivers/MySQL/Messages/LocalInFileRequestMessage.html", "name": "Plasma\\Drivers\\MySQL\\Messages\\LocalInFileRequestMessage", "doc": "&quot;Represents a Local In File Data Message.&quot;"},
                    {"type": "Class", "fromName": "Plasma\\Drivers\\MySQL\\Messages", "fromLink": "Plasma/Drivers/MySQL/Messages.html", "link": "Plasma/Drivers/MySQL/Messages/OkResponseMessage.html", "name": "Plasma\\Drivers\\MySQL\\Messages\\OkResponseMessage", "doc": "&quot;Represents an Ok Response Message.&quot;"},
                    {"type": "Class", "fromName": "Plasma\\Drivers\\MySQL\\Messages", "fromLink": "Plasma/Drivers/MySQL/Messages.html", "link": "Plasma/Drivers/MySQL/Messages/ParseException.html", "name": "Plasma\\Drivers\\MySQL\\Messages\\ParseException", "doc": "&quot;Represents an exception during message parsing.&quot;"},
                                                        {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\Messages\\ParseException", "fromLink": "Plasma/Drivers/MySQL/Messages/ParseException.html", "link": "Plasma/Drivers/MySQL/Messages/ParseException.html#method_setState", "name": "Plasma\\Drivers\\MySQL\\Messages\\ParseException::setState", "doc": "&quot;Sets the new parser state&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\Messages\\ParseException", "fromLink": "Plasma/Drivers/MySQL/Messages/ParseException.html", "link": "Plasma/Drivers/MySQL/Messages/ParseException.html#method_getState", "name": "Plasma\\Drivers\\MySQL\\Messages\\ParseException::getState", "doc": "&quot;Get the new parser state.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\Messages\\ParseException", "fromLink": "Plasma/Drivers/MySQL/Messages/ParseException.html", "link": "Plasma/Drivers/MySQL/Messages/ParseException.html#method_setBuffer", "name": "Plasma\\Drivers\\MySQL\\Messages\\ParseException::setBuffer", "doc": "&quot;Sets the remaining buffer.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\Messages\\ParseException", "fromLink": "Plasma/Drivers/MySQL/Messages/ParseException.html", "link": "Plasma/Drivers/MySQL/Messages/ParseException.html#method_getBuffer", "name": "Plasma\\Drivers\\MySQL\\Messages\\ParseException::getBuffer", "doc": "&quot;Get the remaining buffer.&quot;"},
            {"type": "Class", "fromName": "Plasma\\Drivers\\MySQL\\Messages", "fromLink": "Plasma/Drivers/MySQL/Messages.html", "link": "Plasma/Drivers/MySQL/Messages/PrepareStatementOkMessage.html", "name": "Plasma\\Drivers\\MySQL\\Messages\\PrepareStatementOkMessage", "doc": "&quot;Represents a Prepare Statement Ok Message.&quot;"},
                    {"type": "Class", "fromName": "Plasma\\Drivers\\MySQL", "fromLink": "Plasma/Drivers/MySQL.html", "link": "Plasma/Drivers/MySQL/Statement.html", "name": "Plasma\\Drivers\\MySQL\\Statement", "doc": "&quot;Represents a Prepared Statement.&quot;"},
                                                        {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\Statement", "fromLink": "Plasma/Drivers/MySQL/Statement.html", "link": "Plasma/Drivers/MySQL/Statement.html#method___construct", "name": "Plasma\\Drivers\\MySQL\\Statement::__construct", "doc": "&quot;Constructor.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\Statement", "fromLink": "Plasma/Drivers/MySQL/Statement.html", "link": "Plasma/Drivers/MySQL/Statement.html#method___destruct", "name": "Plasma\\Drivers\\MySQL\\Statement::__destruct", "doc": "&quot;Destructor. Runs once the instance goes out of scope.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\Statement", "fromLink": "Plasma/Drivers/MySQL/Statement.html", "link": "Plasma/Drivers/MySQL/Statement.html#method_getID", "name": "Plasma\\Drivers\\MySQL\\Statement::getID", "doc": "&quot;Get the driver-dependent ID of this statement.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\Statement", "fromLink": "Plasma/Drivers/MySQL/Statement.html", "link": "Plasma/Drivers/MySQL/Statement.html#method_getQuery", "name": "Plasma\\Drivers\\MySQL\\Statement::getQuery", "doc": "&quot;Get the prepared query.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\Statement", "fromLink": "Plasma/Drivers/MySQL/Statement.html", "link": "Plasma/Drivers/MySQL/Statement.html#method_isClosed", "name": "Plasma\\Drivers\\MySQL\\Statement::isClosed", "doc": "&quot;Whether the statement has been closed.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\Statement", "fromLink": "Plasma/Drivers/MySQL/Statement.html", "link": "Plasma/Drivers/MySQL/Statement.html#method_close", "name": "Plasma\\Drivers\\MySQL\\Statement::close", "doc": "&quot;Closes the prepared statement and frees the associated resources on the server.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\Statement", "fromLink": "Plasma/Drivers/MySQL/Statement.html", "link": "Plasma/Drivers/MySQL/Statement.html#method_execute", "name": "Plasma\\Drivers\\MySQL\\Statement::execute", "doc": "&quot;Executes the prepared statement. Resolves with a &lt;code&gt;QueryResult&lt;\/code&gt; instance.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\Statement", "fromLink": "Plasma/Drivers/MySQL/Statement.html", "link": "Plasma/Drivers/MySQL/Statement.html#method_runQuery", "name": "Plasma\\Drivers\\MySQL\\Statement::runQuery", "doc": "&quot;Runs the given querybuilder on the underlying driver instance. However the query will be ignored, only the parameters are used.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\Statement", "fromLink": "Plasma/Drivers/MySQL/Statement.html", "link": "Plasma/Drivers/MySQL/Statement.html#method_getParams", "name": "Plasma\\Drivers\\MySQL\\Statement::getParams", "doc": "&quot;Get the parsed parameters.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\Statement", "fromLink": "Plasma/Drivers/MySQL/Statement.html", "link": "Plasma/Drivers/MySQL/Statement.html#method_getColumns", "name": "Plasma\\Drivers\\MySQL\\Statement::getColumns", "doc": "&quot;Get the columns.&quot;"},
            {"type": "Class", "fromName": "Plasma\\Drivers\\MySQL", "fromLink": "Plasma/Drivers/MySQL.html", "link": "Plasma/Drivers/MySQL/StatementCursor.html", "name": "Plasma\\Drivers\\MySQL\\StatementCursor", "doc": "&quot;Represents a Statement Cursor.&quot;"},
                                                        {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\StatementCursor", "fromLink": "Plasma/Drivers/MySQL/StatementCursor.html", "link": "Plasma/Drivers/MySQL/StatementCursor.html#method___construct", "name": "Plasma\\Drivers\\MySQL\\StatementCursor::__construct", "doc": "&quot;Constructor.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\StatementCursor", "fromLink": "Plasma/Drivers/MySQL/StatementCursor.html", "link": "Plasma/Drivers/MySQL/StatementCursor.html#method___destruct", "name": "Plasma\\Drivers\\MySQL\\StatementCursor::__destruct", "doc": "&quot;Destructor. Runs once the instance goes out of scope.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\StatementCursor", "fromLink": "Plasma/Drivers/MySQL/StatementCursor.html", "link": "Plasma/Drivers/MySQL/StatementCursor.html#method_isClosed", "name": "Plasma\\Drivers\\MySQL\\StatementCursor::isClosed", "doc": "&quot;Whether the cursor has been closed.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\StatementCursor", "fromLink": "Plasma/Drivers/MySQL/StatementCursor.html", "link": "Plasma/Drivers/MySQL/StatementCursor.html#method_close", "name": "Plasma\\Drivers\\MySQL\\StatementCursor::close", "doc": "&quot;Closes the cursor and frees the associated resources on the server.&quot;"},
                    {"type": "Method", "fromName": "Plasma\\Drivers\\MySQL\\StatementCursor", "fromLink": "Plasma/Drivers/MySQL/StatementCursor.html", "link": "Plasma/Drivers/MySQL/StatementCursor.html#method_fetch", "name": "Plasma\\Drivers\\MySQL\\StatementCursor::fetch", "doc": "&quot;Fetches the given amount of rows using the cursor. Resolves with the row, an array of rows (if amount &gt; 1), or false if no more results exist.&quot;"},
            {"type": "Class", "fromName": "Plasma\\Drivers\\MySQL", "fromLink": "Plasma/Drivers/MySQL.html", "link": "Plasma/Drivers/MySQL/StatusFlags.html", "name": "Plasma\\Drivers\\MySQL\\StatusFlags", "doc": "&quot;The MySQL Status Flags.&quot;"},
                    
                                        // Fix trailing commas in the index
        {}
    ];

    /** Tokenizes strings by namespaces and functions */
    function tokenizer(term) {
        if (!term) {
            return [];
        }

        var tokens = [term];
        var meth = term.indexOf('::');

        // Split tokens into methods if "::" is found.
        if (meth > -1) {
            tokens.push(term.substr(meth + 2));
            term = term.substr(0, meth - 2);
        }

        // Split by namespace or fake namespace.
        if (term.indexOf('\\') > -1) {
            tokens = tokens.concat(term.split('\\'));
        } else if (term.indexOf('_') > 0) {
            tokens = tokens.concat(term.split('_'));
        }

        // Merge in splitting the string by case and return
        tokens = tokens.concat(term.match(/(([A-Z]?[^A-Z]*)|([a-z]?[^a-z]*))/g).slice(0,-1));

        return tokens;
    };

    root.Sami = {
        /**
         * Cleans the provided term. If no term is provided, then one is
         * grabbed from the query string "search" parameter.
         */
        cleanSearchTerm: function(term) {
            // Grab from the query string
            if (typeof term === 'undefined') {
                var name = 'search';
                var regex = new RegExp("[\\?&]" + name + "=([^&#]*)");
                var results = regex.exec(location.search);
                if (results === null) {
                    return null;
                }
                term = decodeURIComponent(results[1].replace(/\+/g, " "));
            }

            return term.replace(/<(?:.|\n)*?>/gm, '');
        },

        /** Searches through the index for a given term */
        search: function(term) {
            // Create a new search index if needed
            if (!bhIndex) {
                bhIndex = new Bloodhound({
                    limit: 500,
                    local: searchIndex,
                    datumTokenizer: function (d) {
                        return tokenizer(d.name);
                    },
                    queryTokenizer: Bloodhound.tokenizers.whitespace
                });
                bhIndex.initialize();
            }

            results = [];
            bhIndex.get(term, function(matches) {
                results = matches;
            });

            if (!rootPath) {
                return results;
            }

            // Fix the element links based on the current page depth.
            return $.map(results, function(ele) {
                if (ele.link.indexOf('..') > -1) {
                    return ele;
                }
                ele.link = rootPath + ele.link;
                if (ele.fromLink) {
                    ele.fromLink = rootPath + ele.fromLink;
                }
                return ele;
            });
        },

        /** Get a search class for a specific type */
        getSearchClass: function(type) {
            return searchTypeClasses[type] || searchTypeClasses['_'];
        },

        /** Add the left-nav tree to the site */
        injectApiTree: function(ele) {
            ele.html(treeHtml);
        }
    };

    $(function() {
        // Modify the HTML to work correctly based on the current depth
        rootPath = $('body').attr('data-root-path');
        treeHtml = treeHtml.replace(/href="/g, 'href="' + rootPath);
        Sami.injectApiTree($('#api-tree'));
    });

    return root.Sami;
})(window);

$(function() {

    // Enable the version switcher
    $('#version-switcher').change(function() {
        window.location = $(this).val()
    });

    
        // Toggle left-nav divs on click
        $('#api-tree .hd span').click(function() {
            $(this).parent().parent().toggleClass('opened');
        });

        // Expand the parent namespaces of the current page.
        var expected = $('body').attr('data-name');

        if (expected) {
            // Open the currently selected node and its parents.
            var container = $('#api-tree');
            var node = $('#api-tree li[data-name="' + expected + '"]');
            // Node might not be found when simulating namespaces
            if (node.length > 0) {
                node.addClass('active').addClass('opened');
                node.parents('li').addClass('opened');
                var scrollPos = node.offset().top - container.offset().top + container.scrollTop();
                // Position the item nearer to the top of the screen.
                scrollPos -= 200;
                container.scrollTop(scrollPos);
            }
        }

    
    
        var form = $('#search-form .typeahead');
        form.typeahead({
            hint: true,
            highlight: true,
            minLength: 1
        }, {
            name: 'search',
            displayKey: 'name',
            source: function (q, cb) {
                cb(Sami.search(q));
            }
        });

        // The selection is direct-linked when the user selects a suggestion.
        form.on('typeahead:selected', function(e, suggestion) {
            window.location = suggestion.link;
        });

        // The form is submitted when the user hits enter.
        form.keypress(function (e) {
            if (e.which == 13) {
                $('#search-form').submit();
                return true;
            }
        });

    
});


