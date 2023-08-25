<?php

/**
 * Qubus\Dbal
 *
 * @link       https://github.com/QubusPHP/dbal
 * @copyright  2020
 * @author     Joshua Parker <joshua@joshuaparker.dev>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types=1);

namespace Qubus\Dbal\Collector;

use Closure;
use Qubus\Dbal\Collector;

use function call_user_func_array;
use function count;
use function func_get_args;
use function func_num_args;
use function is_array;
use function is_numeric;

class Where extends Collector
{
    /** @var array  $where  where conditions */
    public array $where = [];

    /** @var array  $orderBy  ORDER BY clause */
    public array $orderBy = [];

    /** @var int  $limit  query limit */
    public int $limit;

    /** @var int $offset  query offset */
    public int $offset;

    /**
     * Alias for andWhere.
     *
     * @param mixed $column Array of 'and where' statements or column name.
     * @param string|null $op Where logic operator.
     * @param mixed|null $value Where value.
     * @return mixed Current instance.
     */
    public function where(mixed $column, string $op = null, mixed $value = null): mixed
    {
        return call_user_func_array([$this, 'andWhere'], func_get_args());
    }

    /**
     * Adds an 'and where' statement to the query.
     *
     * @param mixed $column Array of 'and where' statements or column name.
     * @param string|null $op Where logic operator.
     * @param mixed|null $value Where value.
     * @return static Current instance.
     */
    public function andWhere(mixed $column, string $op = null, mixed $value = null): static
    {
        if ($column instanceof Closure) {
            $this->andWhereOpen();
            $column($this);
            $this->whereClose();

            return $this;
        }

        if (func_num_args() === 2) {
            $value = $op;
            $op = is_array(value: $value) ? 'in' : '=';
        }

        return $this->where__(type: 'and', column: $column, op: $op, value: $value);
    }

    /**
     * Adds an 'or where' statement to the query.
     *
     * @param   mixed   $column  array of 'or where' statements or column name
     * @param string|null $op      where logic operator
     * @param mixed|null $value   where value
     */
    public function orWhere(mixed $column, string $op = null, mixed $value = null): static
    {
        if ($column instanceof Closure) {
            $this->orWhereOpen();
            $column($this);
            $this->whereClose();

            return $this;
        }

        if (func_num_args() === 2) {
            $value = $op;
            $op = is_array(value: $value) ? 'in' : '=';
        }

        return $this->where__(type: 'or', column: $column, op: $op, value: $value);
    }

    /**
     * Alias for andWhere.
     *
     * @param   mixed   $column  array of 'and not where' statements or column name
     * @param string|null $op      where logic operator
     * @param mixed|null $value   where value
     */
    public function notWhere(mixed $column, string $op = null, mixed $value = null)
    {
        return call_user_func_array([$this, 'andNotWhere'], func_get_args());
    }

    /**
     * Adds an 'and not where' statement to the query.
     *
     * @param   mixed   $column  array of 'and where' statements or column name
     * @param string|null $op      where logic operator
     * @param mixed|null $value   where value
     */
    public function andNotWhere(mixed $column, string $op = null, mixed $value = null): static
    {
        if ($column instanceof Closure) {
            $this->andNotWhereOpen();
            $column($this);
            $this->whereClose();

            return $this;
        }

        if (func_num_args() === 2) {
            $value = $op;
            $op = is_array(value: $value) ? 'in' : '=';
        }

        return $this->where__(type: 'and', column: $column, op: $op, value: $value, not: true);
    }

    /**
     * Adds an 'or not where' statement to the query.
     *
     * @param   mixed   $column  array of 'or where' statements or column name
     * @param string|null $op      where logic operator
     * @param mixed|null $value   where value
     */
    public function orNotWhere(mixed $column, string $op = null, mixed $value = null): static
    {
        if ($column instanceof Closure) {
            $this->orNotWhereOpen();
            $column($this);
            $this->whereClose();

            return $this;
        }

        if (func_num_args() === 2) {
            $value = $op;
            $op = is_array(value: $value) ? 'in' : '=';
        }

        return $this->where__(type: 'or', column: $column, op: $op, value: $value, not: true);
    }

    /**
     * Opens an 'and where' nesting.
     */
    public function whereOpen(): static
    {
        $this->where[] = [
            'type'    => 'and',
            'nesting' => 'open',
        ];

        return $this;
    }

    /**
     * Closes an 'and where' nesting.
     */
    public function whereClose(): static
    {
        $this->where[] = [
            'nesting' => 'close',
        ];

        return $this;
    }

    /**
     * Opens an 'and where' nesting.
     */
    public function andWhereOpen(): static
    {
        $this->where[] = [
            'type'    => 'and',
            'nesting' => 'open',
        ];

        return $this;
    }

    /**
     * Closes an 'and where' nesting.
     */
    public function andWhereClose(): static
    {
        return $this->whereClose();
    }

    /**
     * Opens an 'or where' nesting.
     */
    public function orWhereOpen(): static
    {
        $this->where[] = [
            'type'    => 'or',
            'nesting' => 'open',
        ];

        return $this;
    }

    /**
     * Closes an 'or where' nesting.
     */
    public function orWhereClose(): static
    {
        return $this->whereClose();
    }

    /**
     * Opens an 'and not where' nesting.
     */
    public function notWhereOpen(): static
    {
        $this->where[] = [
            'type'    => 'and',
            'not'     => true,
            'nesting' => 'open',
        ];

        return $this;
    }

    /**
     * Closes an 'and not where' nesting.
     */
    public function notWhereClose(): static
    {
        return $this->whereClose();
    }

    /**
     * Opens an 'and not where' nesting.
     */
    public function andNotWhereOpen(): static
    {
        $this->where[] = [
            'type'    => 'and',
            'not'     => true,
            'nesting' => 'open',
        ];

        return $this;
    }

    /**
     * Closes an 'and not where' nesting.
     */
    public function andNotWhereClose(): static
    {
        return $this->whereClose();
    }

    /**
     * Opens an 'or not where' nesting.
     */
    public function orNotWhereOpen(): static
    {
        $this->where[] = [
            'type'    => 'or',
            'not'     => true,
            'nesting' => 'open',
        ];

        return $this;
    }

    /**
     * Closes an 'or where' nesting.
     */
    public function orNotWhereClose(): static
    {
        return $this->whereClose();
    }

    /**
     * Adds an 'and where' statement to the query
     *
     * @param string $type    chain type.
     * @param  mixed  $column  array of 'where' statements or column name.
     * @param string $op      where logic operator.
     * @param  mixed  $value   where value.
     * @param bool $not     whether to use NOT.
     */
    protected function where__(string $type, mixed $column, string $op, mixed $value, bool $not = false): static
    {
        if (is_array(value: $column) && $op = null && $value = null) {
            foreach ($column as $key => $val) {
                if (is_array(value: $val)) {
                    $numArgs = count($val);

                    if ($numArgs === 2) {
                        $this->where[] = [
                            'type'  => $type,
                            'field' => $val[0],
                            'op'    => '=',
                            'value' => $val[1],
                            'not'   => false,
                        ];
                    } elseif ($numArgs === 3) {
                        $this->where[] = [
                            'type'  => $type,
                            'field' => $val[0],
                            'op'    => $val[1],
                            'value' => $val[2],
                            'not'   => false,
                        ];
                    } else {
                        $this->where[] = [
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
            $this->where[] = [
                'type'  => $type,
                'field' => $column,
                'op'    => $op,
                'value' => $value,
                'not'   => $not,
            ];
        }

        return $this;
    }

    /**
     * Adds an 'order by' statement to the query.
     *
     * @param array|string $column Array of statements or column name.
     * @param string|null $direction Optional order direction.
     */
    public function orderBy(array|string $column, ?string $direction = null): static
    {
        if (is_array(value: $column)) {
            foreach ($column as $key => $val) {
                if (is_numeric(value: $key)) {
                    $key = $val;
                    $val = null;
                }

                $this->orderBy[] = [
                    'column'    => $key,
                    'direction' => $val,
                ];
            }
        } else {
            $this->orderBy[] = [
                'column'    => $column,
                'direction' => $direction,
            ];
        }

        return $this;
    }

    /**
     * Sets a limit [and offset] for the query
     *
     * @param   int $limit limit integer
     * @param   int $offset offset integer
     */
    public function limit(int $limit, int $offset = 0): static
    {
        $this->limit = (int) $limit;
        func_num_args() > 1 && $this->offset = (int) $offset;

        return $this;
    }

    /**
     * Sets an offset for the query
     *
     * @param int $offset offset integer
     */
    public function offset(int $offset): static
    {
        $this->offset = (int) $offset;

        return $this;
    }
}
