<?php
/**
 * This file is part of Soloproyectos common library.
 *
 * @author  Gonzalo Chumillas <gchumillas@email.com>
 * @license https://github.com/soloproyectos-php/db/blob/master/LICENSE The MIT License (MIT)
 * @link    https://github.com/soloproyectos-php/db
 */
namespace soloproyectos\db;
use \Mysqli;
use soloproyectos\db\exception\DbException;
use soloproyectos\db\DbSource;
use soloproyectos\event\EventMediator;
use soloproyectos\text\Text;

/**
 * Class DbConnector.
 *
 * @package Db
 * @author  Gonzalo Chumillas <gchumillas@email.com>
 * @license https://github.com/soloproyectos-php/db/blob/master/LICENSE The MIT License (MIT)
 * @link    https://github.com/soloproyectos-php/db
 */
class DbConnector
{
    /**
     * Database connection.
     * @var Mysqli
     */
    private $_conn;
    
    /**
     * Debugger.
     * @var EventMediator
     */
    private $_debugger;
    
    /**
     * Debug listener.
     * @var callable
     */
    private $_debugListener;

    /**
     * Constructor.
     *
     * @param string $dbname   Database name
     * @param string $username User name (not required)
     * @param string $password Password (not required)
     * @param string $server   Server machine (default is 'localhost')
     * @param string $charset  Character set (default is 'utf8')
     */
    public function __construct(
        $dbname, $username = "", $password = "", $server = "localhost", $charset = "utf8"
    ) {
        $this->_debugger = new EventMediator();
        $this->_conn = @mysqli_connect($server, $username, $password);
        if ($this->_conn === false) {
            throw new DbException("Failed to connect to the database");
        }

        @mysqli_select_db($this->_conn, $dbname);
        if ($this->_conn->errno > 0) {
            throw new DbException(
                "{$this->_conn->error} (Error no. {$this->_conn->errno})"
            );
        }

        $this->_conn->set_charset($charset);
    }
    
    /**
     * Starts the debugger mechanism.
     * 
     * @param callable $listener Debug listener
     * @param string   $type     Listener type ('query' or 'exec', not required)
     * 
     * @return void
     */
    public function startDebug($listener = null, $type = "")
    {
        if (!Text::isEmpty($type) && !in_array($type, ["exec", "query"])) {
            throw new DbException("Invalid filter type. Valid filter types are 'exec' and 'query'");
        }
        
        $this->_debugListener = $listener !== null? $listener: function ($sql) {
            echo "$sql\n";
        };
        
        $types = Text::isEmpty($type)? ["exec", "query"]: [$type];
        foreach ($types as $type) {
            $this->_debugger->on($type, $this->_debugListener);
        }
    }
    
    /**
     * Stops the debugger mechanism.
     * 
     * @param string $type Listener type ('query' or 'exec', not required)
     * 
     * @return void
     */
    public function stopDebug($type = "")
    {
        if (!Text::isEmpty($type) && !in_array($type, ["exec", "query"])) {
            throw new DbException("Invalid filter type. Valid filter types are 'exec' and 'query'");
        }
        
        $types = Text::isEmpty($type)? ["exec", "query"]: [$type];
        foreach ($types as $type) {
            $this->_debugger->off($type, $this->_debugListener);
        }
    }
    
    /**
     * Adds a debug listener.
     * 
     * @param Callable $listener Listener
     * @param string   $type     Filter type ('exec' or 'query'. Not required)
     * 
     * @return void
     */
    public function addDebugListener($listener, $type = "")
    {
        if (!Text::isEmpty($type) && !in_array($type, ["exec", "query"])) {
            throw new DbException("Invalid filter type. Valid filter types are 'exec' and 'query'");
        }
        
        $types = Text::isEmpty($type)? ["exec", "query"]: [$type];
        foreach ($types as $type) {
            $this->_debugger->on($type, $listener);
        }
    }

    /**
     * Escapes and quotes a value.
     *
     * For example:
     * ```php
     * $rows = $db->query("select * from mytable where id = " . $db->quote($id));
     * ```
     *
     * In any case, is preferable to write the previous code as follows:
     * ```php
     * $rows = $db->query("select * from mytable where id = ?" . $id);
     * ```
     *
     * @param string|null $value Value
     *
     * @return string
     */
    public function quote($value)
    {
        return is_null($value) ? "null" : "'" . mysqli_real_escape_string($this->_conn, $value) . "'";
    }

    /**
     * Executes an SQL statement.
     *
     * This function executes an SQL statement and returns the number of affected rows.
     *
     * For example:
     * ```php
     * $count = $db->exec("delete from mytable where section = 'mysection'");
     * echo "Number of affected rows: $count";
     * ```
     *
     * @param string       $sql       SQL statement
     * @param scalar|array $arguments List of strings (not required)
     *
     * @return int
     */
    public function exec($sql, $arguments = array())
    {
        $this->_debugger->trigger("exec", [$sql, $arguments]);
        $result = $this->_exec($sql, $arguments);
        return $this->_conn->affected_rows;
    }

    /**
     * Executes a DDL statement.
     *
     * This function executes a DDL statement (select, show, describe, etc...) and returns a datasource.
     *
     * Examples:
     * ```php
     * $db = new DbConnector($dbname, $username, $password);
     *
     * // retrieves a single row
     * $row = $db->query("select count(*) from table");
     * echo $row[0];
     *
     * // retrieves multiple rows
     * $rows = $db->query("select id, name from mytable where section = ?", "my-section");
     * foreach ($rows as $row) {
     *      echo "$row[id]: $row[name]\n";
     * }
     *
     * // uses an array as arguments
     * $rows = $db->query("select id, name from mytable where col1 = ? and col2 = ?", array(101, 102));
     * echo "Number of rows" . count($rows);
     * ```
     *
     * @param string       $sql       SQL statement
     * @param scalar|array $arguments Arguments
     *
     * @return DbSource
     */
    public function query($sql, $arguments = array())
    {
        $this->_debugger->trigger("query", [$sql, $arguments]);
        $ret = new DbSource($this, $sql, $arguments);
        return $ret;
    }

    /**
     * Executes a DDL statement and returns all rows.
     *
     * This function executes a DDL statement (select, show, describe, etc...) and returns an
     * associative array. I recommend to use DbConnector::query() instead.
     *
     * @param string       $sql       SQL statement
     * @param scalar|array $arguments List of arguments (not required)
     *
     * @return array
     */
    public function fetchRows($sql, $arguments = array())
    {
        $ret = array();
        $result = $this->_exec($sql, $arguments);

        // fetches all rows
        while ($row = $result->fetch_array()) {
            array_push($ret, $row);
        }
        $result->close();

        return $ret;
    }

    /**
     * Gets the last inserted id.
     *
     * @return string
     */
    public function getLastInsertId()
    {
        $ds = new DbSource($this, "select last_insert_id()");
        return "" . $ds[0];
    }

    /**
     * Closes the database connection.
     *
     * @return void
     */
    public function close()
    {
        $this->_conn->close();
    }

    /**
     * Executes an SQL statement.
     *
     * @param string       $sql       SQL statement
     * @param scalar|array $arguments List of arguments (not required)
     *
     * @return Mysqli_result
     */
    private function _exec($sql, $arguments = array())
    {
        $sql = $this->_replaceArguments($sql, $arguments);

        // executes the statement
        $result = $this->_conn->query($sql);
        if ($this->_conn->errno > 0) {
            throw new DbException(
                "Failed to execute the statement: ({$this->_conn->errno}) {$this->_conn->error}"
            );
        }

        return $result;
    }

    /**
     * Replaces arguments in an SQL statement.
     *
     * @param string       $sql       SQL statement
     * @param scalar|array $arguments List of arguments
     *
     * @return string
     */
    private function _replaceArguments($sql, $arguments)
    {
        if (!is_array($arguments)) {
            $arguments = array($arguments);
        }
        
        // searches string segments (startPos, endPos)
        $stringSegments = [];
        if (preg_match_all('/(["\'`])((?:\\\\\1|.)*?)\1/', $sql, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[2] as $match) {
                $startPos = $match[1];
                $endPos = $startPos + strlen($match[0]);
                array_push($stringSegments, [$startPos, $endPos]);
            }
        }
        
        // searches arguments position
        $argsPos = [];
        preg_match_all('/\?/', $sql, $matches, PREG_OFFSET_CAPTURE);
        foreach ($matches[0] as $match) {
            array_push($argsPos, $match[1]);
        }

        // replaces arguments
        $matchCount = 0;
        $argCount = 0;
        return preg_replace_callback(
            '/\?/',
            function ($matches) use (&$argCount, &$matchCount, $arguments, $argsPos, $stringSegments) {
                $ret = $matches[0];
                
                if ($argCount < count($arguments)) {
                    // is the current match inside a quoted string?
                    $argPos = $argsPos[$matchCount];
                    $isInsideQuotedString = false;
                    foreach ($stringSegments as $segment) {
                        if ($argPos >= $segment[0] &&  $argPos < $segment[1]) {
                            $isInsideQuotedString = true;
                            break;
                        }
                    }
                    
                    if (!$isInsideQuotedString) {
                        $ret = $this->quote($arguments[$argCount++]);
                    }
                }
                
                $matchCount++;
                return $ret;
            },
            $sql
        );
    }
}
