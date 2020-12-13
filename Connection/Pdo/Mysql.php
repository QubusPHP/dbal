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
     */
    public function listTables()
    {
        $query = DB::query('SHOW TABLES', DB::SELECT)->asAssoc();

        $result = $query->execute($this);

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
        $query = DB::query('SHOW DATABASES', DB::SELECT)->asAssoc();

        $result = $query->execute($this);

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
        $query = DB::query('SHOW FULL COLUMNS FROM ' . $this->quoteIdentifier($table), DB::SELECT)->asAssoc();

        $result = $query->execute($this);

        $return = [];

        foreach ($result as $r) {
            $type = $r['Type'];

            if (strpos($type, ' ')) {
                [$type, $extra] = explode(' ', $type, 2);
            }

            if ($pos = strpos($type, '(')) {
                $field['type'] = substr($type, 0, $pos);
                $field['constraint'] = substr($type, $pos + 1, -1);
            } else {
                $field['constraint'] = null;
            }

            $field['extra'] = $extra ?? null;

            $field['name'] = $r['Field'];
            $field['default'] = $r['Default'];
            $field['null'] = $r['Null'] !== 'No';
            $field['privileges'] = explode(',', $r['Privileges']);
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
     *
     * @return object $this;
     */
    public function startTransaction()
    {
        $this->pdoInstance->query('SET AUTOCOMMIT=0');
        $this->pdoInstance->query('START TRANSACTION');

        return $this;
    }

    public function commitTransaction()
    {
        $this->pdoInstance->query('COMMIT');
        $this->pdoInstance->query('SET AUTOCOMMIT=1');

        return $this;
    }

    public function rollbackTransaction()
    {
        $this->pdoInstance->query('ROLLBACK');
        $this->pdoInstance->query('SET AUTOCOMMIT=1');

        return $this;
    }
}
