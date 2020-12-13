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

use Qubus\Dbal\Collector;
use Qubus\Dbal\DB;

use function array_keys;
use function array_merge;
use function array_pop;
use function count;
use function is_array;
use function reset;

class Insert extends Collector
{
    /** @var  string  $type  query type */
    protected $type = DB::INSERT;

    /** @var  string  $insertIdField  field used for lastInsertId */
    public $insertIdField;

    /** @var  array  $columns  columns to use */
    public array $columns = [];

    /** @var  array  $values  values for insert */
    public array $values = [];

    public function __construct($table, $values = [])
    {
        $this->into($table);
        $this->values($values);
    }

    /**
     * Sets/Gets the field used for lastInsertId
     *
     * @param   string
     * @return  mixed  current instance when setting, string fieldname when gettting.
     */
    public function insertIdField($field = null)
    {
        if ($field) {
            $this->insertIdField = $field;

            return $this;
        }

        return $this->insertIdField;
    }

    /**
     * Sets the table to insert into.
     *
     * @param   string  $table  table to insert into
     * @return  object  $this
     */
    public function into($table)
    {
        $this->table = $table;
        return $this;
    }

    /**
     * Adds values to insert
     *
     * @param   array   $values  array or collection of arrays to insert
     * @param   bool    $merge   wether to merge the values with the last inserted set
     * @return  object  $this
     */
    public function values($values = [], $merge = false)
    {
        if (empty($values)) {
            return $this;
        }

        is_array(reset($values)) || $values = [$values];

        foreach ($values as $v) {
            $keys = array_keys($v);
            $this->columns = array_merge($this->columns, $keys);

            if ($merge && count($this->values)) {
                $last = array_pop($this->values);
                $this->values[] = array_merge($last, $v);
            } else {
                $this->values[] = $v;
            }
        }

        return $this;
    }
}
