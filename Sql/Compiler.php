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
use Qubus\Dbal\Connection;
use Qubus\Dbal\Query;

use function array_merge;
use function is_array;
use function is_string;
use function preg_replace;
use function substr;
use function trim;

abstract class Compiler
{
    /** @var ?Connection $connection Dbal connection object. */
    protected ?Connection $connection = null;

    /** @var  array  $query  query commands */
    protected array $query = [];

    /**
     * @param Connection $connection Dbal connection object.
     */
    public function __construct(Connection &$connection)
    {
        $this->connection = $connection;
    }

    /**
     * Compiles the query.
     *
     * @param mixed $query Query object.
     */
    public function compile(mixed $query, ?string $type = null, array $bindings = []): string
    {
        // ensure an instance of Base
        if (! $query instanceof Base) {
            $query = new Query(query: $query, type: $type, bindings: $bindings);
        }

        // get the query contents
        $contents = $query->getContents();

        // merge the bindings
        $queryBindings = $query->getBindings();
        $bindings = array_merge($queryBindings, $bindings);

        // process the bindings
        $contents = $this->processBindings(contents: $contents, bindings: $bindings);

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
     * @param mixed $contents Query contents.
     * @param array $bindings An array of query bindings.
     */
    protected function processBindings(mixed $contents, array $bindings, bool $first = true): mixed
    {
        if ($first && empty($bindings)) {
            return $contents;
        }

        if (is_array(value: $contents)) {
            foreach ($contents as $i => &$v) {
                $contents[$i] = $this->processBindings(contents: $v, bindings: $bindings, first: false);
            }
        } elseif (is_string(value: $contents)) {
            foreach ($bindings as $from => $to) {
                substr(string: $from, offset: 0, length: 1) !== ':' && $from = ':' . $from;
                $contents = preg_replace(pattern: '/' . $from . '/', replacement: $to, subject: $contents);
            }
        }

        return $contents;
    }

    /**
     * Value quoting shortcut.
     *
     * @param mixed $value Value to quote.
     * @return int|string Quoted value.
     */
    protected function quote(mixed $value): int|string
    {
        return $this->connection->quote(value: $value);
    }

    /**
     * Identifier quoting shortcut.
     *
     * @param   mixed   $identifier  identifier to quote
     * @return  string  quoted value
     */
    protected function quoteIdentifier(mixed $identifier): string
    {
        return $this->connection->quoteIdentifier(value: $identifier);
    }
}
