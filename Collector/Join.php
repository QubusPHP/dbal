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

namespace Qubus\Dbal\Collector;

use function call_user_func_array;
use function func_get_args;
use function func_num_args;

class Join
{
    /** @var  string  $table  table to join */
    protected $table;

    /** @var  string  $type  join type */
    protected $type;

    /** @var  array  $on  array of on statements */
    protected $on = [];

    /**
     * Join Contructor.
     *
     * @param  string  $table  table name
     * @param  string  $type   type of join
     */
    public function __construct($table, $type = null)
    {
        $this->table = $table;
        $this->type = $type;
    }

    /**
     * Adds an 'on' clause for the join.
     *
     * @param   string|array  $column  string column name or array for alias
     * @param   string        $op      logic operator
     * @param   string|array  $value   value or array for alias
     */
    public function on($column, $op = null, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $op;
            $op = '=';
        }

        $this->on[] = [$column, $op, $value, 'AND'];
    }

    /**
     * Adds an 'on' clause for the join.
     *
     * @param   string|array  $column  string column name or array for alias
     * @param   string        $op      logic operator
     * @param   string|array  $value   value or array for alias
     */
    public function andOn($column, $op = null, $value = null)
    {
        call_user_func_array([$this, 'on'], func_get_args());
    }

    /**
     * Adds an 'on' clause for the join.
     *
     * @param   string|array  $column  string column name or array for alias
     * @param   string        $op      logic operator
     * @param   string|array  $value   value or array for alias
     */
    public function orOn($column, $op = null, $value = null)
    {
        if (func_num_args() === 2) {
            $value = $op;
            $op = '=';
        }

        $this->on[] = [$column, $op, $value, 'OR'];
    }

    /**
     * Returns the join as a command array.
     *
     * @return array Join command array.
     */
    public function asArray()
    {
        return [
            'table' => $this->table,
            'type'  => $this->type,
            'on'    => $this->on,
        ];
    }
}
