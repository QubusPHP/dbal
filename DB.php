<?php

/**
 * Qubus\Dbal
 *
 * @link       https://github.com/QubusPHP/dbal
 * @copyright  2020 Joshua Parker
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Dbal;

use Qubus\Dbal\Collector\Delete;
use Qubus\Dbal\Collector\Insert;
use Qubus\Dbal\Collector\Select;
use Qubus\Dbal\Collector\Update;
use Qubus\ValueObjects\DateTime\DateTime;

use function func_get_args;

/**
 * @method  Select  select()      Create select query.
 * @method  Query             query()       Returns a query object.
 * @method  Select  selectArray() Creates a select object.
 * @method  Update  update()      Creates and update object.
 * @method  Delete  delete()      Creates a delete object.
 */
class DB
{
    /**
     * Query type contants.
     */
    public const PLAIN                 = 'Plain';
    public const INSERT                = 'Insert';
    public const SELECT                = 'Select';
    public const UPDATE                = 'Update';
    public const DELETE                = 'Delete';

    protected static $connection;

    /**
     * Retrieve a database connection.
     *
     * @param   array   $config  database connection config
     * @return  object  a new Qubus\Dbal\Cconnection\[type] object
     */
    public static function connection($config = [])
    {
        return self::$connection = Connection::instance($config);
    }

    /**
     * Database expression shortcut.
     *
     * @param   mixed  $expression
     * @return  object  a new Qubus\Dbal\Expression object.
     */
    public static function expr($expression): Expression
    {
        return new Expression($expression);
    }

    /**
     * Database value shortcut.
     *
     * @param   mixed   $value  value
     * @return  object  a new Qubus\Dbal\Value object.
     */
    public static function value($value)
    {
        return new Value($value);
    }

    /**
     * Database identifier shortcut.
     *
     * @param   mixed   $identifier  identifier
     * @return  object  a new Qubus\Dbal\Value object.
     */
    public static function identifier($identifier): Identifier
    {
        return new Identifier($identifier);
    }

    /**
     * Database function shortcut.
     *
     * @param   string  $fnc      function
     * @param   array   $params  function params
     * @return  object  a new Qubus\Dbal\Fnc object.
     */
    public static function fnc(?string $fnc, $params = []): Fnc
    {
        return new Fnc($fnc, $params);
    }

    /**
     * Returns a query object.
     *
     * @param   mixed   $query     raw database query
     * @param   string  $type      query type
     * @param   array   $bindings  query bindings
     * @return  object  Qubus\Dbal\Query
     */
    public static function query($query, $type, array $bindings = []): Query
    {
        return new Query($query, $type, $bindings);
    }

    /**
     * Create a select collector object.
     *
     * @param mixed  String field names or arrays for alias
     * @return object Select query collector object.
     */
    public static function select($column = null): Select
    {
        $query = new Select();
        return $query->selectArray(func_get_args());
    }

    /**
     * Creates a select collector object.->select('user_login', 'user_fname')
     *
     * @param   array   $columns  array of fields to select
     * @return  object  select query collector object
     */
    public static function selectArray($columns = [])
    {
        return static::select()->selectArray($columns);
    }

    /**
     * Creates an update collector object.
     *
     * @param string   $table  table to update
     * @param array    $set    associative array of new values
     * @return object   update query collector object
     */
    public static function update($table, array $set = []): Update
    {
        return new Update($table, $set);
    }

    /**
     * Creates a delete collector object.
     *
     * @param string   $table  table to delete from
     * @return object   delete query collector object
     */
    public static function delete($table = null): Delete
    {
        return new Delete($table);
    }

    /**
     * Creates an insert collector object.
     *
     * @param string   $table  table to insert into
     * @return object   insert query collector object
     */
    public static function insert($table): Insert
    {
        return new Insert($table);
    }

    /**
     * Creates a schema collector object.
     *
     * @return object schema query collector object
     */
    public static function schema(): Schema
    {
        return new Schema(self::$connection);
    }

    /**
     * Return an Immutable YYYY-MM-DD HH:II:SS date format.
     *
     * @return string Immutable datetime string.
     */
    public static function now()
    {
        return DateTime::now();
    }
}
