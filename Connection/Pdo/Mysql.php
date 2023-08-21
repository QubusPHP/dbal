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
use function explode;
use function reset;
use function strpos;
use function substr;

class Mysql extends DbalPdo
{
    /**
     * Get an array of table names from the connection.
     *
     * @return array tables names
     * @throws Exception
     */
    public function listTables(): array
    {
        $query = DB::query(query: 'SHOW TABLES', type: DB::SELECT)->asAssoc();

        $result = $query->execute(connection: $this);

        return array_map(callback: function ($r) {
            return reset($r);
        }, array: $result);
    }

    /**
     * Get an array of database names from the connection.
     *
     * @return  array  database names
     * @throws Exception
     */
    public function listDatabases(): array
    {
        $query = DB::query(query: 'SHOW DATABASES', type: DB::SELECT)->asAssoc();

        $result = $query->execute(connection: $this);

        return array_map(callback: function ($r) {
            return reset($r);
        }, array: $result);
    }

    /**
     * Get an array of table fields from a table.
     *
     * @return array field arrays
     * @throws Exception
     */
    public function listFields($table): array
    {
        $query = DB::query(query: 'SHOW FULL COLUMNS FROM ' . $this->quoteIdentifier(value: $table), type: DB::SELECT)->asAssoc();

        $result = $query->execute(connection: $this);

        $return = [];

        foreach ($result as $r) {
            $type = $r['Type'];

            if (strpos(haystack: $type, needle: ' ')) {
                [$type, $extra] = explode(separator: ' ', string: $type, limit: 2);
            }

            if ($pos = strpos(haystack: $type, needle: '(')) {
                $field['type'] = substr(string: $type, offset: 0, length: $pos);
                $field['constraint'] = substr(string: $type, offset: $pos + 1, length: -1);
            } else {
                $field['constraint'] = null;
            }

            $field['extra'] = $extra ?? null;

            $field['name'] = $r['Field'];
            $field['default'] = $r['Default'];
            $field['null'] = $r['Null'] !== 'No';
            $field['privileges'] = explode(separator: ',', string: $r['Privileges']);
            $field['key'] = $r['Key'];
            $field['comments'] = $r['Comment'];
            $field['collation'] = $r['Collation'];

            if ($r['Extra'] === 'auto_increment') {
                $field['auto_increment'] = true;
            } else {
                $field['auto_increment'] = false;
            }

            $return[$field['name']] = $field;
        }

        return $return;
    }

    /**
     * Start a transaction.
     */
    public function startTransaction(): static
    {
        $this->pdoInstance->query(statement: 'SET AUTOCOMMIT=0');
        $this->pdoInstance->query(statement: 'START TRANSACTION');

        return $this;
    }

    public function commitTransaction(): static
    {
        $this->pdoInstance->query(statement: 'COMMIT');
        $this->pdoInstance->query(statement: 'SET AUTOCOMMIT=1');

        return $this;
    }

    public function rollbackTransaction(): static
    {
        $this->pdoInstance->query(statement: 'ROLLBACK');
        $this->pdoInstance->query(statement: 'SET AUTOCOMMIT=1');

        return $this;
    }
}
