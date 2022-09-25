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
    /** @var  ?string  $table  table to join */
    protected ?string $table = null;

    /** @var  ?string $type  join type */
    protected ?string $type = null;

    /** @var  array  $on  array of on statements */
    protected array $on = [];

    /**
     * Join Constructor.
     *
     * @param string $table  table name
     * @param string|null $type   type of join
     */
    public function __construct(string $table, ?string $type = null)
    {
        $this->table = $table;
        $this->type = $type;
    }

    /**
     * Adds an 'on' clause for the join.
     *
     * @param array|string $column  string column name or array for alias
     * @param string|null $op      logic operator
     * @param array|string|null $value   value or array for alias
     */
    public function on(array|string $column, string $op = null, array|string $value = null): void
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
     * @param array|string $column  string column name or array for alias
     * @param string|null $op      logic operator
     * @param array|string|null $value   value or array for alias
     */
    public function andOn(array|string $column, string $op = null, array|string $value = null): void
    {
        call_user_func_array([$this, 'on'], func_get_args());
    }

    /**
     * Adds an 'on' clause for the join.
     *
     * @param array|string $column  string column name or array for alias
     * @param string|null $op      logic operator
     * @param array|string|null $value   value or array for alias
     */
    public function orOn(array|string $column, string $op = null, array|string $value = null): void
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
    public function asArray(): array
    {
        return [
            'table' => $this->table,
            'type'  => $this->type,
            'on'    => $this->on,
        ];
    }
}
