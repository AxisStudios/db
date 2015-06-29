<?php
/**
 * This file is part of Soloproyectos common library.
 *
 * @author  Gonzalo Chumillas <gchumillas@email.com>
 * @license https://github.com/soloproyectos-php/db/blob/master/LICENSE The MIT License (MIT)
 * @link    https://github.com/soloproyectos-php/db
 */
namespace soloproyectos\db;
use \ArrayAccess;
use \Countable;
use \Iterator;

/**
 * Class DbSource.
 *
 * @package Db
 * @author  Gonzalo Chumillas <gchumillas@email.com>
 * @license https://github.com/soloproyectos-php/db/blob/master/LICENSE The MIT License (MIT)
 * @link    https://github.com/soloproyectos-php/db
 */
class DbSource implements ArrayAccess, Iterator, Countable
{
    /**
     * List of rows.
     * @var array of array
     */
    private $_rows = array();

    /**
     * Constructor.
     *
     * @param Db           $db        Database connection
     * @param string       $sql       DDL statement (select, show, describe...)
     * @param scalar|array $arguments List of arguments passed the the statement (not required)
     */
    public function __construct($db, $sql, $arguments = array())
    {
        $this->_db = $db;
        $this->_rows = $this->_db->fetchRows($sql, $arguments);
    }

    /***************************
     * Implements ArrayAccess. *
     ***************************/

    /**
     * Does the column exist?
     *
     * @param string $columnName Column name
     *
     * @return boolean
     */
    public function offsetExists($columnName)
    {
        $row = current($this->_rows);
        return array_key_exists($columnName, $row);
    }

    /**
     * Gets the column value.
     *
     * @param string $columnName Column name
     *
     * @return string|null
     */
    public function offsetGet($columnName)
    {
        $row = current($this->_rows);
        return $row !== false? $row[$columnName] : null;
    }

    /**
     * Sets the column value.
     *
     * @param string $columnName Column name
     * @param mixed  $value      Value
     *
     * @return void
     */
    public function offsetSet($columnName, $value)
    {
        $this->_rows[key($this->_rows)][$columnName] = "$value";
    }

    /**
     * Removes a column.
     *
     * @param string $columnName Column name
     *
     * @return void
     */
    public function offsetUnset($columnName)
    {
        unset($this->_rows[key($this->_rows)][$columnName]);
    }

    /************************
     * Implements Iterator. *
     ************************/

    /**
     * Gets the current row.
     *
     * This function returns 'false' if the internal pointer has exceeded the end of the list.
     *
     * @return array|boolean
     */
    public function current()
    {
        return current($this->_rows);
    }

    /**
     * Gets the next row.
     *
     * This function returns 'false' if the internal pointer has reached the end of the list.
     *
     * @return array|boolean
     */
    public function next()
    {
        return next($this->_rows);
    }

    /**
     * Gets the internal pointer.
     *
     * @return integer
     */
    public function key()
    {
        return key($this->_rows);
    }

    /**
     * Rewinds the internal pointer.
     *
     * @return void
     */
    public function rewind()
    {
        reset($this->_rows);
    }

    /**
     * Is the current internal pointer valid?
     *
     * @return bool
     */
    public function valid()
    {
        return key($this->_rows) !== null;
    }

    /*************************
     * Implements Countable. *
     *************************/

     /**
      * Gets the number of rows.
      *
      * @return integer
      */
    public function count()
    {
        return count($this->_rows);
    }
}
