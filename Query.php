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

namespace Qubus\Dbal;

class Query extends Base
{
    /** @var mixed Raw query (string for sql, array for NoSQL). */
    protected mixed $query;

    /**
     * Constructor, sets the query, type and bindings.
     *
     * @param mixed $query Raw query.
     * @param string $type Query type.
     * @param array $bindings Query bindings.
     */
    public function __construct(mixed $query, string $type, array $bindings = [])
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
    public function getContents(): mixed
    {
        return $this->query;
    }
}
