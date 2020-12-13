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

namespace Qubus\Dbal\Sql;

use Qubus\Dbal\Base;
use Qubus\Dbal\Query;

use function array_merge;
use function is_array;
use function is_string;
use function preg_replace;
use function substr;
use function trim;

abstract class Compiler
{
    /** @var object $connection Dbal connection object. */
    protected $connection;

    /** @var  array  $query  query commands */
    protected array $query = [];

    /**
     * @param object $connection Dbal connection object.
     */
    public function __construct(&$connection)
    {
        $this->connection = $connection;
    }

    /**
     * Compiles the query.
     *
     * @param object $query Query object.
     */
    public function compile($query, ?string $type = null, array $bindings = [])
    {
        // ensure an instance of Base
        if (! $query instanceof Base) {
            $query = new Query($query, $type, $bindings);
        }

        // get the query contents
        $contents = $query->getContents();

        // merge the bindings
        $queryBindings = $query->getBindings();
        $bindings = array_merge($queryBindings, $bindings);

        // process the bindings
        $contents = $this->processBindings($contents, $bindings);

        // returns when it is a raw string
        if (is_string($contents)) {
            return $contents;
        }

        /**
         * Since we can compile subqueries, store the old query
         * and set the new one.
         */
        $oldQuery = $this->query;
        $this->query = $contents;

        // Compile the query according to it's type.
        $result = $this->{'compile' . $type}();
        is_string($result) && $result = trim($result);

        // Set back the old query
        $this->query = $oldQuery;

        return is_string($result) ? trim($result) : $result;
    }

    /**
     * Processes all the query bindings recursively.
     *
     * @param mixes $contents Query contents.
     * @param array $bindings An array of query bindings.
     */
    protected function processBindings($contents, array $bindings, bool $first = true)
    {
        if ($first && empty($bindings)) {
            return $contents;
        }

        if (is_array($contents)) {
            foreach ($contents as $i => &$v) {
                $contents[$i] = $this->processBindings($v, $bindings, false);
            }
        } elseif (is_string($contents)) {
            foreach ($bindings as $from => $to) {
                substr($from, 0, 1) !== ':' && $from = ':' . $from;
                $contents = preg_replace('/' . $from . '/', $to, $contents);
            }
        }

        return $contents;
    }

    /**
     * Value quoting shotcut.
     *
     * @param  mixed $value  Value to quote.
     * @return string Quoted value.
     */
    protected function quote($value)
    {
        return $this->connection->quote($value);
    }

    /**
     * Identifier quoting shotcut.
     *
     * @param   mixed   $identifier  identifier to quote
     * @return  string  quoted value
     */
    protected function quoteIdentifier($identifier)
    {
        return $this->connection->quoteIdentifier($identifier);
    }
}
