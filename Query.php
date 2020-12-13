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

class Query extends Base
{
    /** @var mixed Raw query (string for sql, array for NoSQL). */
    protected $query;

    /**
     * Constructor, sets the query, type and bindings.
     *
     * @param mixed Raw query.
     * @param string Query type.
     * @param array  Query bindings.
     */
    public function __construct($query, $type, $bindings = [])
    {
        $this->query = $query;
        $this->type = $type;
        $this->bindings = $bindings;
    }

    /**
     * Get the query value.
     *
     * @return  mixed  query contents
     */
    public function getContents()
    {
        return $this->query;
    }
}
