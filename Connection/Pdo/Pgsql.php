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

use function array_map;
use function reset;

class Pgsql extends DbalPdo
{
    /** @var string $tableQuote  table quote */
    protected static $tableQuote = '"';

    /**
     * Get an array of table names from the connection.
     *
     * @return  array  tables names
     */
    public function listTables()
    {
        $result = DB::query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public'", DB::SELECT)
            ->asAssoc()
            ->execute($this);

        return array_map(function ($r) {
            return reset($r);
        }, $result);
    }

    /**
     * Get an array of database names from the connection.
     *
     * @return  array  database names
     */
    public function listDatabases()
    {
        $result = DB::query("SELECT datname FROM pg_database", DB::SELECT)
            ->asAssoc()
            ->execute($this);

        return array_map(function ($r) {
            return reset($r);
        }, $result);
    }

    /**
     * Get an array of table fields from a table.
     *
     * @return  array  field arrays
     */
    public function listFields($table)
    {
        $query = DB::query("SELECT * FROM information_schema.columns WHERE table_name = :table", DB::SELECT, [$this->quote($table)])
            ->asAssoc();

        $result = $query->execute($this);

        return array_map(function ($r) {
            return reset($r);
        }, $result);
    }

    /**
     * Start a transaction.
     *
     * @return  object  $this;
     */
    public function startTransaction()
    {
        $this->pdoInstance->query('BEGIN');

        return $this;
    }

    /**
     * Commit a transaction.
     *
     * @return  object  $this;
     */
    public function commitTransaction()
    {
        $this->pdoInstance->query('COMMIT');

        return $this;
    }

    /**
     * Roll back a transaction.
     *
     * @return  object  $this;
     */
    public function rollbackTransaction()
    {
        $this->pdoInstance->query('ROLLBACK');

        return $this;
    }
}
