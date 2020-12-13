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
use function strpos;
use function substr;

class Sqlite extends DbalPdo
{
    /**
     * Sets the connection encoding.
     *
     * @param  string  $charset  encoding
     */
    protected function setCharset($charset)
    {
        // skip setting the character set
    }

    public function listTables()
    {
        return array_map(function ($i) {
            return reset($i);
        }, $this->query("SELECT name FROM sqlite_master WHERE type = 'table'"
            . " AND name != 'sqlite_sequence' AND name != 'geometry_columns'"
            . " AND name != 'spatial_ref_sys' "
            . "UNION ALL SELECT name FROM sqlite_temp_master "
            . "WHERE type = 'table' ORDER BY name", DB::SELECT)
            ->asAssoc()
            ->execute());
    }

    public function listFields($table)
    {
        return array_map(function ($i) {
            $field = [
                'name' => $i['name'],
            ];

            $type = $i['type'];

            if ($pos = strpos($type, '(')) {
                $field['type'] = substr($type, 0, $pos);
                $field['constraint'] = substr($type, $pos + 1, -1);
            } else {
                $field['constraint'] = null;
            }

            $field['null'] = ! (bool) $i['notnull'];
            $field['default'] = $i['dflt_value'];
            $field['primary'] = (bool) $i['pk'];

            return $field;
        }, $this->query('Pragma table_info(' . $this->quoteIdentifier($table) . ')', DB::SELECT)
            ->asAssoc()
            ->execute());
    }

    public function startTransaction()
    {
        $this->pdoInstance->query('BEGIN');

        return $this;
    }

    public function commitTransaction()
    {
        $this->pdoInstance->query('COMMIT');

        return $this;
    }

    public function rollbackTransaction()
    {
        $this->pdoInstance->query('ROLLBACK');

        return $this;
    }
}
