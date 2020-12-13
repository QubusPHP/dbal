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

    /** @var  array  $offset  query offset */
    public $offset;

    /**
     * Alias for andWhere.
     *
     * @param   mixed   $column  array of 'and where' statements or column name
     * @param   string  $op      where logic operator
     * @param   mixed   $value   where value
     * @return  object  current instance
     */
    public function where($column, $op = null, $value = null)
    {
        return call_user_func_array([$this, 'andWhere'], func_get_args());
    }

    /**
     * Adds an 'and where' statement to the query.
     *
     * @param   mixed   $column  array of 'and where' statements or column name
     * @param   string  $op      where logic operator
     * @param   mixed   $value   where value
     * @return  object  current instance
     */
    public function andWhere($column, $op = null, $value = null)
    {
        if ($column instanceof Closure) {
            $this->andWhereOpen();
            $column($this);
            $this->whereClose();

            return $this;
        }

        if (func_num_args() === 2) {
            $value = $op;
            $op = is_array($value) ? 'in' : '=';
        }

        return $this->where__('and', $column, $op, $value);
    }

    /**
     * Adds an 'or where' statement to the query.
     *
     * @param   mixed   $column  array of 'or where' statements or column name
     * @param   string  $op      where logic operator
     * @param   mixed   $value   where value
     * @return  object  current instance
     */
    public function orWhere($column, $op = null, $value = null)
    {
        if ($column instanceof Closure) {
            $this->orWhereOpen();
            $column($this);
            $this->whereClose();

            return $this;
        }

        if (func_num_args() === 2) {
            $value = $op;
            $op = is_array($value) ? 'in' : '=';
        }

        return $this->where__('or', $column, $op, $value);
    }

    /**
     * Alias for andWhere.
     *
     * @param   mixed   $column  array of 'and not where' statements or column name
     * @param   string  $op      where logic operator
     * @param   mixed   $value   where value
     * @return  object  current instance
     */
    public function notWhere($column, $op = null, $value = null)
    {
        return call_user_func_array([$this, 'andNotWhere'], func_get_args());
    }

    /**
     * Adds an 'and not where' statement to the query.
     *
     * @param   mixed   $column  array of 'and where' statements or column name
     * @param   string  $op      where logic operator
     * @param   mixed   $value   where value
     * @return  object  current instance
     */
    public function andNotWhere($column, $op = null, $value = null)
    {
        if ($column instanceof Closure) {
            $this->andNotWhereOpen();
            $column($this);
            $this->whereClose();

            return $this;
        }

        if (func_num_args() === 2) {
            $value = $op;
            $op = is_array($value) ? 'in' : '=';
        }

        return $this->where__('and', $column, $op, $value, true);
    }

    /**
     * Adds an 'or not where' statement to the query.
     *
     * @param   mixed   $column  array of 'or where' statements or column name
     * @param   string  $op      where logic operator
     * @param   mixed   $value   where value
     * @return  object  Current instance.
     */
    public function orNotWhere($column, $op = null, $value = null)
    {
        if ($column instanceof Closure) {
            $this->orNotWhereOpen();
            $column($this);
            $this->whereClose();

            return $this;
        }

        if (func_num_args() === 2) {
            $value = $op;
            $op = is_array($value) ? 'in' : '=';
        }

        return $this->where__('or', $column, $op, $value, true);
    }

    /**
     * Opens an 'and where' nesting.
     *
     * @return  object  current instance
     */
    public function whereOpen()
    {
        $this->where[] = [
            'type'    => 'and',
            'nesting' => 'open',
        ];

        return $this;
    }

    /**
     * Closes an 'and where' nesting.
     *
     * @return  object  current instance
     */
    public function whereClose()
    {
        $this->where[] = [
            'nesting' => 'close',
        ];

        return $this;
    }

    /**
     * Opens an 'and where' nesting.
     *
     * @return  object  current instance
     */
    public function andWhereOpen()
    {
        $this->where[] = [
            'type'    => 'and',
            'nesting' => 'open',
        ];

        return $this;
    }

    /**
     * Closes an 'and where' nesting.
     *
     * @return  object  current instance
     */
    public function andWhereClose()
    {
        return $this->whereClose();
    }

    /**
     * Opens an 'or where' nesting.
     *
     * @return  object  current instance
     */
    public function orWhereOpen()
    {
        $this->where[] = [
            'type'    => 'or',
            'nesting' => 'open',
        ];

        return $this;
    }

    /**
     * Closes an 'or where' nesting.
     *
     * @return  object  current instance
     */
    public function orWhereClose()
    {
        return $this->whereClose();
    }

    /**
     * Opens an 'and not where' nesting.
     *
     * @return  object  current instance
     */
    public function notWhereOpen()
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
     *
     * @return  object  current instance
     */
    public function notWhereClose()
    {
        return $this->whereClose();
    }

    /**
     * Opens an 'and not where' nesting.
     *
     * @return  object  current instance
     */
    public function andNotWhereOpen()
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
     *
     * @return  object  current instance
     */
    public function andNotWhereClose()
    {
        return $this->whereClose();
    }

    /**
     * Opens an 'or not where' nesting.
     *
     * @return  object  current instance
     */
    public function orNotWhereOpen()
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
     *
     * @return object Current instance.
     */
    public function orNotWhereClose()
    {
        return $this->whereClose();
    }

    /**
     * Adds an 'and where' statement to the query
     *
     * @param  string $type    chain type
     * @param  mixed  $column  array of 'where' statements or column name
     * @param  string $op      where logic operator
     * @param  mixed  $value   where value
     * @param  bool   $not     wether to use NOT
     * @return object Current instance
     */
    protected function where__($type, $column, $op, $value, $not = false)
    {
        if (is_array($column) && $op = null && $value = null) {
            foreach ($column as $key => $val) {
                if (is_array($val)) {
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
     * Adds an 'order by' statment to the query.
     *
     * @param   string|array  $column     Array of statements or column name.
     * @param   string        $direction  Optional order direction.
     * @return  object        current instance
     */
    public function orderBy($column, ?string $direction = null)
    {
        if (is_array($column)) {
            foreach ($column as $key => $val) {
                if (is_numeric($key)) {
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
     * @param   int     limit integer
     * @param   int     offset integer
     * @return  object  current instance
     */
    public function limit(int $limit, int $offset = 0)
    {
        $this->limit = (int) $limit;
        func_num_args() > 1 && $this->offset = (int) $offset;

        return $this;
    }

    /**
     * Sets an offset for the query
     *
     * @param   int     offset integer
     * @return  object  current instance
     */
    public function offset($offset)
    {
        $this->offset = (int) $offset;

        return $this;
    }
}
