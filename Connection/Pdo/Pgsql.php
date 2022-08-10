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

namespace Qubus\Dbal\Connection\Pdo;

use Qubus\Dbal\Connection\DbalPdo;
use Qubus\Dbal\DB;

use Qubus\Exception\Exception;

use function array_map;
use function reset;

class Pgsql extends DbalPdo
{
    /** @var string $tableQuote  table quote */
    protected static string $tableQuote = '"';

    /**
     * Get an array of table names from the connection.
     *
     * @return  array  tables names.
     * @throws Exception
     */
    public function listTables(): array
    {
        $result = DB::query(query: "SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'", type: DB::SELECT)
            ->asAssoc()
            ->execute(connection: $this);

        return array_map(callback: function ($r) {
            return reset($r);
        }, array: $result);
    }

    /**
     * Get an array of database names from the connection.
     *
     * @return  array  database names.
     * @throws Exception
     */
    public function listDatabases(): array
    {
        $result = DB::query(query: "SELECT datname FROM pg_database", type: DB::SELECT)
            ->asAssoc()
            ->execute(connection: $this);

        return array_map(callback: function ($r) {
            return reset($r);
        }, array: $result);
    }

    /**
     * Get an array of table fields from a table.
     *
     * @return  array  field arrays
     * @throws Exception
     */
    public function listFields($table): array
    {
        $query = DB::query(
            query: "SELECT * FROM information_schema.columns WHERE table_name = :table",
            type: DB::SELECT,
            bindings: [$this->quote(value: $table)]
        )
            ->asAssoc();

        $result = $query->execute(connection: $this);

        return array_map(callback: function ($r) {
            return reset($r);
        }, array: $result);
    }

    /**
     * Start a transaction.
     */
    public function startTransaction(): static
    {
        $this->pdoInstance->query(statement: 'BEGIN');

        return $this;
    }

    /**
     * Commit a transaction.
     */
    public function commitTransaction(): static
    {
        $this->pdoInstance->query(statement: 'COMMIT');

        return $this;
    }

    /**
     * Roll back a transaction.
     */
    public function rollbackTransaction(): static
    {
        $this->pdoInstance->query(statement: 'ROLLBACK');

        return $this;
    }
}
