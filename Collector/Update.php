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

use Qubus\Dbal\DB;

use function is_array;

class Update extends Where
{
    /** @var  string  $type  query type */
    protected string $type = DB::UPDATE;

    protected array $values;

    /**
     * Constructor, sets the table name
     */
    public function __construct($table = null)
    {
        $table && $this->table = $table;
    }

    /**
     * Sets the table to update
     *
     * @param string $table  table to update
     */
    public function table(string $table): static
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Set the new values
     *
     * @param   mixed   $key    string field name or associative values array
     * @param mixed|null $value  new value
     */
    public function set(mixed $key, mixed $value = null): static
    {
        is_array(value: $key) || $key = [$key => $value];

        foreach ($key as $k => $v) {
            $this->values[$k] = $v;
        }

        return $this;
    }
}
