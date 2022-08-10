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

use Closure;
use Qubus\Dbal\DB;
use Qubus\Exception\Exception;

use function array_merge;
use function call_user_func_array;
use function count;
use function func_get_args;
use function func_num_args;
use function is_array;

class Select extends Where
{
    /** @var string $type  query type */
    protected string $type = DB::SELECT;

    /** @var array $having  having conditions */
    public array $having = [];

    /** @var array $groupBy  GROUP BY clause */
    public array $groupBy = [];

    /** @var Join $lastJoin Last join object */
    protected Join $lastJoin;

    /** @var array $joins  query joins */
    public array $joins = [];

    /** @var array $columns  columns to use */
    public array $columns = [];

    /** @var bool $columns  Whether to use distinct */
    public bool $distinct = false;

    /**
     * Constructor
     *
     * @param mixed|null $column
     */
    public function __construct(mixed $column = null)
    {
        $columns = func_get_args();

        if (count($columns)) {
            $this->columns = $columns;
        }
    }

    /**
     * Set the table to select from.
     *
     * @param mixed $table Table to select from.
     * @param ...
     */
    public function from($table): static
    {
        $tables = func_get_args();

        $this->table = array_merge($this->table, $tables);

        return $this;
    }

    /**
     * Sets/adds columns to select.
     *
     * @param mixed $column Column name or array($column, $alias) or object.
     * @param ...
     */
    public function select(mixed $column = null): static
    {
        $this->columns = array_merge($this->columns, func_get_args());

        return $this;
    }

    /**
     * Empty the select array.
     */
    public function resetSelect(): static
    {
        $this->columns = [];

        return $this;
    }

    /**
     * Choose the columns to select from, using an array.
     *
     * @param array $columns List of column names or aliases
     */
    public function selectArray(array $columns = []): static
    {
        ! empty($columns) && $this->columns = array_merge($this->columns, $columns);

        return $this;
    }

    /**
     * Enables or disables selecting only unique (distinct) values.
     *
     * @param bool $distinct Enable or disable distinct values.
     */
    public function distinct(bool $distinct = true): static
    {
        $this->distinct = $distinct;

        return $this;
    }

    /**
     * Creates a "GROUP BY ..." filter.
     *
     * @param mixed $columns Column name or array($column, $alias).
     * @param   ...
     */
    public function groupBy(mixed $columns): static
    {
        $columns = func_get_args();

        $this->groupBy = array_merge($this->groupBy, $columns);

        return $this;
    }

    /**
     * Adds a new join.
     *
     * @param string $table String column name or alias array.
     * @param string|null $type Join type.
     */
    public function join(string $table, ?string $type = null): static
    {
        $this->join[] = $this->lastJoin = new Join(table: $table, type: $type);

        return $this;
    }

    /**
     * Sets an "AND ON" clause on the last join.
     *
     * @param string $column1 column name
     * @param string $op logic operator
     * @param string|null $column2 column name
     * @throws Exception
     */
    public function on(string $column1, string $op, ?string $column2 = null): static
    {
        if (! $this->lastJoin) {
            throw new Exception(message: 'You must first join a table before setting an "ON" clause.');
        }

        call_user_func_array(callback: [$this->lastJoin, 'on'], args: func_get_args());

        return $this;
    }

    /**
     * Sets an "AND ON" clause on the last join.
     *
     * @param string $column1 column name
     * @param string $op logic operator
     * @param string|null $column2 column name
     * @throws Exception
     */
    public function andOn(string $column1, string $op, ?string $column2 = null): static
    {
        if (! $this->lastJoin) {
            throw new Exception(message: 'You must first join a table before setting an "AND ON" clause.');
        }

        call_user_func_array(callback: [$this->lastJoin, 'andOn'], args: func_get_args());

        return $this;
    }

    /**
     * Sets an "OR ON" clause on the last join.
     *
     * @param string $column1 column name
     * @param string $op logic operator
     * @param string|null $column2 column name
     * @throws Exception
     */
    public function orOn(string $column1, string $op, string $column2 = null): static
    {
        if (! $this->lastJoin) {
            throw new Exception(message: 'You must first join a table before setting an "OR ON" clause.');
        }

        call_user_func_array(callback: [$this->lastJoin, 'orOn'], args: func_get_args());

        return $this;
    }

    /**
     * Alias for andHaving.
     *
     * @param mixed $column Array of 'and having' statements or column name.
     * @param string|null $op Having logic operator.
     * @param mixed $value Having value
     * @return mixed
     */
    public function having(mixed $column, ?string $op = null, mixed $value = null): mixed
    {
        return call_user_func_array(callback: [$this, 'andHaving'], args: func_get_args());
    }

    /**
     * Alias for andNotHaving.
     *
     * @param mixed $column Array of 'and not having' statements or column name.
     * @param string|null $op Having logic operator.
     * @param mixed|null $value Having value.
     */
    public function notHaving(mixed $column, ?string $op = null, mixed $value = null): mixed
    {
        return call_user_func_array(callback: [$this, 'andNotHaving'], args: func_get_args());
    }

    /**
     * Adds an 'and having' statement to the query.
     *
     * @param   mixed   $column  Array of 'and having' statements or column name.
     * @param string|null $op    Having logic operator.
     * @param mixed|null $value  Having value.
     */
    public function andHaving(mixed $column, ?string $op = null, mixed $value = null): static
    {
        if ($column instanceof Closure) {
            $this->andHavingOpen();
            $column($this);
            $this->andHavingClose();
            return $this;
        }

        if (func_num_args() === 2) {
            $value = $op;
            $op = is_array(value: $value) ? 'in' : '=';
        }

        return $this->having__(type: 'and', column: $column, op: $op, value: $value);
    }

    /**
     * Adds an 'and not having' statement to the query.
     *
     * @param mixed $column Array of 'and not having' statements or column name
     * @param string|null $op Having logic operator.
     * @param mixed|null $value Having value.
     */
    public function andNotHaving(mixed $column, ?string $op = null, mixed $value = null): static
    {
        if ($column instanceof Closure) {
            $this->andNotHavingOpen();
            $column($this);
            $this->andNotHavingClose();
            return $this;
        }

        if (func_num_args() === 2) {
            $value = $op;
            $op = is_array(value: $value) ? 'in' : '=';
        }

        return $this->having__(type: 'and', column: $column, op: $op, value: $value, not: true);
    }

    /**
     * Adds an 'or having' statement to the query.
     *
     * @param mixed $column Array of 'or having' statements or column name.
     * @param string|null $op Having logic operator.
     * @param mixed|null $value Having value.
     */
    public function orHaving(mixed $column, ?string $op = null, mixed $value = null): static
    {
        if ($column instanceof Closure) {
            $this->orHavingOpen();
            $column($this);
            $this->havingClose();
            return $this;
        }

        if (func_num_args() === 2) {
            $value = $op;
            $op = is_array(value: $value) ? 'in' : '=';
        }

        return $this->having__(type: 'or', column: $column, op: $op, value: $value);
    }

    /**
     * Adds an 'or having' statement to the query.
     *
     * @param mixed $column Array of 'or having' statements or column name.
     * @param string|null $op Having logic operator.
     * @param mixed|null $value Having value.
     */
    public function orNotHaving(mixed $column, string $op = null, mixed $value = null): static
    {
        if ($column instanceof Closure) {
            $this->orNotHavingOpen();
            $column($this);
            $this->havingClose();
            return $this;
        }

        if (func_num_args() === 2) {
            $value = $op;
            $op = '=';
        }

        return $this->having__(type: 'or', column: $column, op: $op, value: $value, not: true);
    }

    /**
     * Opens an 'and having' nesting.
     */
    public function havingOpen(): static
    {
        $this->having[] = [
            'type'    => 'and',
            'nesting' => 'open',
        ];

        return $this;
    }

    /**
     * Opens an 'and having' nesting.
     */
    public function notHavingOpen(): static
    {
        $this->having[] = [
            'type'    => 'and',
            'not'     => true,
            'nesting' => 'open',
        ];

        return $this;
    }

    /**
     * Closes an 'and having' nesting.
     */
    public function havingClose(): static
    {
        $this->having[] = [
            'nesting' => 'close',
        ];

        return $this;
    }

    /**
     * Closes an 'and having' nesting.
     */
    public function notHavingClose(): static
    {
        return $this->havingClose();
    }

    /**
     * Opens an 'and having' nesting.
     */
    public function andHavingOpen(): static
    {
        $this->having[] = [
            'type'    => 'and',
            'nesting' => 'open',
        ];

        return $this;
    }

    /**
     * Opens an 'and having' nesting.
     */
    public function andNotHavingOpen(): static
    {
        $this->having[] = [
            'type'    => 'and',
            'not'     => true,
            'nesting' => 'open',
        ];

        return $this;
    }

    /**
     * Closes an 'and having' nesting.
     */
    public function andHavingClose(): static
    {
        return $this->havingClose();
    }

    /**
     * Closes an 'and not having' nesting.
     */
    public function andNotHavingClose(): static
    {
        return $this->havingClose();
    }

    /**
     * Opens an 'or having' nesting.
     */
    public function orHavingOpen(): static
    {
        $this->having[] = [
            'type'    => 'or',
            'nesting' => 'open',
        ];

        return $this;
    }

    /**
     * Opens an 'or having' nesting.
     */
    public function orNotHavingOpen(): static
    {
        $this->having[] = [
            'type'    => 'or',
            'not'     => true,
            'nesting' => 'open',
        ];

        return $this;
    }

    /**
     * Closes an 'or having' nesting.
     */
    public function orHavingClose(): static
    {
        return $this->havingClose();
    }

    /**
     * Closes an 'or having' nesting.
     */
    public function orNotHavingClose(): static
    {
        return $this->havingClose();
    }

    /**
     * Adds an 'and having' statement to the query.
     *
     * @param mixed $column Array of 'and having' statements or column name.
     * @param string $op Having logic operator.
     * @param mixed $value Having value.
     * @param bool $not Whether to use NOT.
     */
    protected function having__($type, mixed $column, string $op, mixed $value, bool $not = false): static
    {
        if (is_array(value: $column) && $op = null && $value = null) {
            foreach ($column as $key => $val) {
                if (is_array(value: $val)) {
                    $numArgs = count($val);

                    if ($numArgs === 2) {
                        $this->having[] = [
                            'type'  => $type,
                            'field' => $val[0],
                            'op'    => '=',
                            'value' => $val[1],
                            'not'   => false,
                        ];
                    } elseif ($numArgs === 3) {
                        $this->having[] = [
                            'type'  => $type,
                            'field' => $val[0],
                            'op'    => $val[1],
                            'value' => $val[1],
                            'not'   => false,
                        ];
                    } else {
                        $this->having[] = [
                            'type'  => $type,
                            'field' => $val[0],
                            'op'    => $val[1],
                            'value' => $val[2],
                            'not'   => $val[3],
                        ];
                    }
                }
            }
        } else {
            $this->having[] = [
                'type'  => $type,
                'field' => $column,
                'op'    => $op,
                'value' => $value,
                'not'   => $not,
            ];
        }

        return $this;
    }
}
