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

/* ===========================================================================
 * Copyright 2013-2015 Marius Sarca
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ============================================================================ */

declare(strict_types=1);

namespace Qubus\Dbal;

use Qubus\Dbal\Connection;
use Qubus\Dbal\Schema\AlterTable;
use Qubus\Dbal\Schema\CreateTable;
use Qubus\Exception\Exception;

use function array_keys;
use function Qubus\Support\Helpers\is_null__;
use function strtolower;

class Schema
{
    /** @var Connection Connection. */
    protected Connection $connection;

    /** @var array|null $tableList Table list. */
    protected ?array $tableList = null;

    /** @var string $currentDatabase Currently used database name. */
    protected string $currentDatabase;

    /** @var array $columns Column list */
    protected array $columns = [];

    /**
     * Constructor.
     *
     * @param Connection $connection Connection.
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Get the name of the currently used database.
     *
     * @return  string
     * @throws Exception
     */
    public function getCurrentDatabase(): string
    {
        if (is_null__(var: $this->currentDatabase)) {
            $compiler = $this->connection->schemaCompiler();
            $result = $compiler->currentDatabase(dsn: $this->connection->getDsn());

            if (isset($result['result'])) {
                $this->currentDatabase = $result['result'];
            } else {
                $this->currentDatabase = $this->connection->column(sql: $result['sql'], params: $result['params']);
            }
        }

        return $this->currentDatabase;
    }

    /**
     * Check if the specified table exists.
     *
     * @param string $table Table name.
     * @param bool $clear (optional) Refresh table list.
     * @throws Exception
     */
    public function hasTable(string $table, bool $clear = false): bool
    {
        $list = $this->getTables(clear: $clear);
        return isset($list[strtolower(string: $table)]);
    }

    /**
     * Get a list with all tables that belong to the currently used database.
     *
     * @param bool $clear (optional) Refresh table list.
     * @return string[]
     * @throws Exception
     */
    public function getTables(bool $clear = false): array
    {
        if ($clear) {
            $this->tableList = null;
        }

        if (is_null__($this->tableList)) {
            $compiler = $this->connection->schemaCompiler();

            $database = $this->getCurrentDatabase();

            $sql = $compiler->getTables(database: $database);

            $results = $this->connection
                ->query(sql: $sql['sql'], params: $sql['params'])
                ->fetchNum()
                ->all();

            $this->tableList = [];

            foreach ($results as $result) {
                $this->tableList[strtolower(string: $result[0])] = $result[0];
            }
        }

        return $this->tableList;
    }

    /**
     * Get a list with all columns that belong to the specified table.
     *
     * @param bool $clear (optional) Refresh column list.
     * @param bool $names (optional) Return only the column names.
     * @return false|string[]
     * @throws Exception
     */
    public function getColumns(string $table, bool $clear = false, bool $names = true): array|bool
    {
        if ($clear) {
            unset($this->columns[$table]);
        }

        if (! $this->hasTable(table: $table, clear: $clear)) {
            return false;
        }

        if (! isset($this->columns[$table])) {
            $compiler = $this->connection->schemaCompiler();

            $database = $this->getCurrentDatabase();

            $sql = $compiler->getColumns(database: $database, table: $table);

            $results = $this->connection
                ->query(sql: $sql['sql'], params: $sql['params'])
                ->fetchAssoc()
                ->all();

            $columns = [];

            foreach ($results as $ord => &$col) {
                $columns[$col['name']] = [
                    'name' => $col['name'],
                    'type' => $col['type'],
                ];
            }

            $this->columns[$table] = $columns;
        }

        return $names ? array_keys(array: $this->columns[$table]) : $this->columns[$table];
    }

    /**
     * Creates a new table.
     *
     * @param string $table Table name.
     * @param callable $callback A callback that will define table's fields and indexes.
     * @throws Exception
     */
    public function create(string $table, callable $callback): void
    {
        $compiler = $this->connection->schemaCompiler();

        $schema = new CreateTable(table: $table);

        $callback($schema);

        foreach ($compiler->create(schema: $schema) as $result) {
            $this->connection->command(sql: $result['sql'], params: $result['params']);
        }

        //clear table list
        $this->tableList = null;
    }

    /**
     * Alters a table's definition.
     *
     * @param string $table Table name
     * @param callable $callback A callback that will add or remove fields or indexes.
     * @throws Exception
     */
    public function alter(string $table, callable $callback): void
    {
        $compiler = $this->connection->schemaCompiler();

        $schema = new AlterTable(table: $table);

        $callback($schema);

        unset($this->columns[strtolower($table)]);

        foreach ($compiler->alter(schema: $schema) as $result) {
            $this->connection->command(sql: $result['sql'], params: $result['params']);
        }
    }

    /**
     * Change a table's name.
     *
     * @param string $table The table.
     * @param string $name The new name of the table.
     * @throws Exception
     */
    public function renameTable(string $table, string $name): void
    {
        $result = $this->connection->schemaCompiler()->renameTable(current: $table, new: $name);
        $this->connection->command(sql: $result['sql'], params: $result['params']);
        $this->tableList = null;
        unset($this->columns[strtolower(string: $table)]);
    }

    /**
     * Deletes a table.
     *
     * @param string $table Table name.
     * @throws Exception
     */
    public function drop(string $table): void
    {
        $compiler = $this->connection->schemaCompiler();

        $result = $compiler->drop(table: $table);

        $this->connection->command(sql: $result['sql'], params: $result['params']);

        //clear table list
        $this->tableList = null;
        unset($this->columns[strtolower(string: $table)]);
    }

    /**
     * Deletes all records from a table.
     *
     * @param string $table Table name.
     * @throws Exception
     */
    public function truncate(string $table): void
    {
        $compiler = $this->connection->schemaCompiler();

        $result = $compiler->truncate(table: $table);

        $this->connection->command(sql: $result['sql'], params: $result['params']);
    }
}
