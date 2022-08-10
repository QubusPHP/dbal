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
     * @param string $charset  encoding
     */
    public function setCharset(string $charset)
    {
        if ($charset) {
            $this->pdoInstance->exec(statement: 'PRAGMA encoding = ' . $this->quote(value: $charset));
        }
    }

    public function listTables(): array
    {
        return array_map(callback: function ($i) {
            return reset($i);
        }, array: DB::query(query: "SELECT name FROM sqlite_master WHERE type = 'table'"
            . " AND name != 'sqlite_sequence' AND name != 'geometry_columns'"
            . " AND name != 'spatial_ref_sys' "
            . "UNION ALL SELECT name FROM sqlite_temp_master "
            . "WHERE type = 'table' ORDER BY name", type:  DB::SELECT)
            ->asAssoc()
            ->execute());
    }

    public function listFields(mixed $table): array
    {
        return array_map(callback: function ($i) {
            $field = [
                'name' => $i['name'],
            ];

            $type = $i['type'];

            if ($pos = strpos(haystack: $type, needle: '(')) {
                $field['type'] = substr(string: $type, offset: 0, length: $pos);
                $field['constraint'] = substr(string: $type, offset: $pos + 1, length: -1);
            } else {
                $field['constraint'] = null;
            }

            $field['null'] = ! (bool) $i['notnull'];
            $field['default'] = $i['dflt_value'];
            $field['primary'] = (bool) $i['pk'];

            return $field;
        }, array: DB::query(query: 'Pragma table_info(' . $this->quoteIdentifier(value: $table) . ')', type: DB::SELECT)
            ->asAssoc()
            ->execute());
    }

    public function startTransaction(): static
    {
        $this->pdoInstance->query(statement: 'BEGIN');

        return $this;
    }

    public function commitTransaction(): static
    {
        $this->pdoInstance->query(statement: 'COMMIT');

        return $this;
    }

    public function rollbackTransaction(): static
    {
        $this->pdoInstance->query(statement: 'ROLLBACK');

        return $this;
    }
}
