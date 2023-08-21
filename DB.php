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
use Qubus\Exception\Exception;
use Qubus\ValueObjects\DateTime\DateTime;
use Qubus\ValueObjects\DateTime\Exception\InvalidDateException;

use function func_get_args;

/**
 * @method  Select  select()      Create select query.
 * @method  Query   query()       Returns a query object.
 * @method  Select  selectArray() Creates a select object.
 * @method  Update  update()      Creates and update object.
 * @method  Delete  delete()      Creates a delete object.
 */
class DB
{
    /**
     * Query type constants.
     */
    public const PLAIN                 = 'Plain';
    public const INSERT                = 'Insert';
    public const SELECT                = 'Select';
    public const UPDATE                = 'Update';
    public const DELETE                = 'Delete';

    protected static Connection $connection;

    /**
     * Retrieve a database connection.
     *
     * @param array $config database connection config
     * @throws Exception
     */
    public static function connection(array $config = []): Connection
    {
        return self::$connection = Connection::instance($config);
    }

    /**
     * Database expression shortcut.
     *
     * @param mixed $expression
     * @return Expression
     */
    public static function expr(mixed $expression): Expression
    {
        return new Expression($expression);
    }

    /**
     * Database value shortcut.
     *
     * @param   mixed   $value  value
     */
    public static function value(mixed $value): Value
    {
        return new Value($value);
    }

    /**
     * Database identifier shortcut.
     *
     * @param   mixed   $identifier  identifier
     */
    public static function identifier(mixed $identifier): Identifier
    {
        return new Identifier($identifier);
    }

    /**
     * Database function shortcut.
     *
     * @param string|null $fnc function
     * @param mixed $params function params
     * @return Fnc
     */
    public static function fnc(?string $fnc, mixed $params = []): Fnc
    {
        return new Fnc(fnc: $fnc, params: $params);
    }

    /**
     * Returns a query object.
     *
     * @param   mixed   $query     raw database query
     * @param   string  $type      query type
     * @param   array   $bindings  query bindings
     */
    public static function query(mixed $query, string $type, array $bindings = []): Query
    {
        return new Query($query, $type, $bindings);
    }

    /**
     * Create a select collector object.
     *
     * @param mixed $column String field names or arrays for alias
     */
    public static function select(mixed $column = null): Select
    {
        $query = new Select();
        return $query->selectArray(func_get_args());
    }

    /**
     * Creates a select collector object.->select('user_login', 'user_fname')
     *
     * @param array $columns  array of fields to select
     */
    public static function selectArray(array $columns = []): Select
    {
        return static::select()->selectArray($columns);
    }

    /**
     * Creates an update collector object.
     *
     * @param string   $table  table to update
     * @param array    $set    associative array of new values
     */
    public static function update(string $table, array $set = []): Update
    {
        return new Update($table, $set);
    }

    /**
     * Creates a delete collector object.
     *
     * @param string|null $table table to delete from
     * @return Delete
     */
    public static function delete(?string $table = null): Delete
    {
        return new Delete($table);
    }

    /**
     * Creates an insert collector object.
     *
     * @param string   $table  table to insert into
     */
    public static function insert(string $table): Insert
    {
        return new Insert($table);
    }

    /**
     * Creates a schema collector object.
     */
    public static function schema(): Schema
    {
        return new Schema(self::$connection);
    }

    /**
     * Return an Immutable YYYY-MM-DD HH:II:SS date format.
     *
     * @return DateTime Immutable datetime object.
     * @throws InvalidDateException
     */
    public static function now(): DateTime
    {
        return DateTime::now();
    }
}
