<?php

/**
 * Qubus\Dbal
 *
 * @link       https://github.com/QubusPHP/dbal
 * @copyright  2020
 * @author     Joshua Parker <joshua@joshuaparker.dev>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
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

namespace Qubus\Dbal\Schema;

use Qubus\Dbal\Connection;

use function array_map;
use function implode;
use function in_array;
use function is_bool;
use function is_numeric;
use function is_string;
use function sprintf;
use function str_replace;
use function strtoupper;
use function trim;
use function ucfirst;

class Compiler
{
    protected string $separator = ';';

    protected string $wrapper = '"%s"';

    /** @var array $params */
    protected array $params = [];

    /** @var string[] $modifiers */
    protected array $modifiers = ['unsigned', 'nullable', 'default', 'autoincrement'];

    /** @var string[] $serials */
    protected array $serials = ['tiny', 'small', 'normal', 'medium', 'big'];

    /** @var string $autoincrement */
    protected string $autoincrement = 'AUTO_INCREMENT';

    protected Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @param array $options
     * @return $this
     */
    public function setOptions(array $options): self
    {
        foreach ($options as $name => $value) {
            $this->{$name} = $value;
        }

        return $this;
    }

    protected function wrap(string $name): string
    {
        return sprintf($this->wrapper, $name);
    }

    /**
     * @param string[] $value
     */
    protected function wrapArray(array $value, string $separator = ', '): string
    {
        return implode(separator: $separator, array: array_map(callback: [$this, 'wrap'], array: $value));
    }

    /**
     * @param float|bool|int|string|null $value
     * @return float|int|string
     */
    protected function value(float|bool|int|string|null $value): float|int|string
    {
        if (is_numeric(value: $value)) {
            return $value;
        }

        if (is_bool(value: $value)) {
            return $value ? 1 : 0;
        }

        if (is_string(value: $value)) {
            return "'" . str_replace(search: "'", replace: "''", subject: $value) . "'";
        }

        return 'NULL';
    }

    /**
     * @param BaseColumn[] $columns
     */
    protected function handleColumns(array $columns): string
    {
        $sql = [];

        foreach ($columns as $column) {
            $line = $this->wrap(name: $column->getName());
            $line .= $this->handleColumnType(column: $column);
            $line .= $this->handleColumnModifiers(column: $column);
            $sql[] = $line;
        }

        return implode(separator: ",\n", array: $sql);
    }

    protected function handleColumnType(BaseColumn $column): string
    {
        $type = 'handleType' . ucfirst(string: $column->getType());
        $result = trim(string: $this->{$type}($column));

        if ($result !== '') {
            $result = ' ' . $result;
        }

        return $result;
    }

    protected function handleColumnModifiers(BaseColumn $column): string
    {
        $line = '';

        foreach ($this->modifiers as $modifier) {
            $callback = 'handleModifier' . ucfirst(string: $modifier);
            $result = trim(string: $this->{$callback}($column));

            if ($result !== '') {
                $result = ' ' . $result;
            }

            $line .= $result;
        }

        return $line;
    }

    protected function handleTypeInteger(BaseColumn $column): string
    {
        return 'INT';
    }

    protected function handleTypeFloat(BaseColumn $column): string
    {
        return 'FLOAT';
    }

    protected function handleTypeDouble(BaseColumn $column): string
    {
        return 'DOUBLE';
    }

    protected function handleTypeDecimal(BaseColumn $column): string
    {
        return 'DECIMAL';
    }

    protected function handleTypeBoolean(BaseColumn $column): string
    {
        return 'BOOLEAN';
    }

    protected function handleTypeBinary(BaseColumn $column): string
    {
        return 'BLOB';
    }

    protected function handleTypeText(BaseColumn $column): string
    {
        return 'TEXT';
    }

    protected function handleTypeString(BaseColumn $column): string
    {
        return 'VARCHAR(' . $this->value(value: $column->get(name: 'length', default: 255)) . ')';
    }

    protected function handleTypeFixed(BaseColumn $column): string
    {
        return 'CHAR(' . $this->value(value: $column->get(name: 'length', default: 255)) . ')';
    }

    protected function handleTypeTime(BaseColumn $column): string
    {
        return 'TIME';
    }

    protected function handleTypeTimestamp(BaseColumn $column): string
    {
        return 'TIMESTAMP';
    }

    protected function handleTypeDate(BaseColumn $column): string
    {
        return 'DATE';
    }

    protected function handleTypeDateTime(BaseColumn $column): string
    {
        return 'DATETIME';
    }

    protected function handleModifierUnsigned(BaseColumn $column): string
    {
        return $column->get(name: 'unsigned', default: false) ? 'UNSIGNED' : '';
    }

    protected function handleModifierNullable(BaseColumn $column): string
    {
        if ($column->get(name: 'nullable', default: true)) {
            return '';
        }

        return 'NOT NULL';
    }

    protected function handleModifierDefault(BaseColumn $column): string
    {
        return null === $column->get(name: 'default') ? '' : 'DEFAULT ' . $this->value(value: $column->get(name: 'default'));
    }

    protected function handleModifierAutoincrement(BaseColumn $column): string
    {
        if ($column->getType() !== 'integer' || ! in_array(needle: $column->get(name: 'size', default: 'normal'), haystack: $this->serials)) {
            return '';
        }

        return $column->get(name: 'autoincrement', default: false) ? $this->autoincrement : '';
    }

    protected function handlePrimaryKey(CreateTable $schema): string
    {
        if (null === $pk = $schema->getPrimaryKey()) {
            return '';
        }

        return ",\n" . 'CONSTRAINT ' . $this->wrap(name: $pk['name']) . ' PRIMARY KEY (' . $this->wrapArray(value: $pk['columns']) . ')';
    }

    protected function handleUniqueKeys(CreateTable $schema): string
    {
        $indexes = $schema->getUniqueKeys();

        if (empty($indexes)) {
            return '';
        }

        $sql = [];

        foreach ($schema->getUniqueKeys() as $name => $columns) {
            $sql[] = 'CONSTRAINT ' . $this->wrap(name: $name) . ' UNIQUE (' . $this->wrapArray(value: $columns) . ')';
        }

        return ",\n" . implode(separator: ",\n", array: $sql);
    }

    /**
     * @return string[]
     */
    protected function handleIndexKeys(CreateTable $schema): array
    {
        $indexes = $schema->getIndexes();

        if (empty($indexes)) {
            return [];
        }

        $sql = [];
        $table = $this->wrap(name: $schema->getTableName());

        foreach ($indexes as $name => $columns) {
            $sql[] = 'CREATE INDEX ' . $this->wrap(name: $name) . ' ON ' . $table . '(' . $this->wrapArray(value: $columns) . ')';
        }

        return $sql;
    }

    protected function handleForeignKeys(CreateTable $schema): string
    {
        /** @var ForeignKey[] $keys */
        $keys = $schema->getForeignKeys();

        if (empty($keys)) {
            return '';
        }

        $sql = [];

        foreach ($keys as $name => $key) {
            $cmd = 'CONSTRAINT ' . $this->wrap(name: $name) . ' FOREIGN KEY (' . $this->wrapArray(value: $key->getColumns()) . ') ';
            $cmd .= 'REFERENCES ' . $this->wrap(name: $key->getReferencedTable()) . ' (' . $this->wrapArray(value: $key->getReferencedColumns()) . ')';

            foreach ($key->getActions() as $actionName => $action) {
                $cmd .= ' ' . $actionName . ' ' . $action;
            }

            $sql[] = $cmd;
        }

        return ",\n" . implode(separator: ",\n", array: $sql);
    }

    protected function handleEngine(CreateTable $schema): string
    {
        if (null !== $engine = $schema->getEngine()) {
            return ' ENGINE = ' . strtoupper(string: $engine);
        }

        return '';
    }

    /**
     * @param AlterTable $table
     * @param $data
     * @return string
     */
    protected function handleDropPrimaryKey(AlterTable $table, $data): string
    {
        return 'ALTER TABLE ' . $this->wrap(name: $table->getTableName()) . ' DROP CONSTRAINT ' . $this->wrap(name: $data);
    }

    /**
     * @param AlterTable $table
     * @param $data
     * @return string
     */
    protected function handleDropUniqueKey(AlterTable $table, $data): string
    {
        return 'ALTER TABLE ' . $this->wrap(name: $table->getTableName()) . ' DROP CONSTRAINT ' . $this->wrap(name: $data);
    }

    /**
     * @param AlterTable $table
     * @param $data
     * @return string
     */
    protected function handleDropIndex(AlterTable $table, $data): string
    {
        return 'DROP INDEX ' . $this->wrap(name: $table->getTableName()) . '.' . $this->wrap(name: $data);
    }

    /**
     * @param AlterTable $table
     * @param $data
     * @return string
     */
    protected function handleDropForeignKey(AlterTable $table, $data): string
    {
        return 'ALTER TABLE ' . $this->wrap(name: $table->getTableName()) . ' DROP CONSTRAINT ' . $this->wrap(name: $data);
    }

    /**
     * @param AlterTable $table
     * @param $data
     * @return string
     */
    protected function handleDropColumn(AlterTable $table, $data): string
    {
        return 'ALTER TABLE ' . $this->wrap(name: $table->getTableName()) . ' DROP COLUMN ' . $this->wrap(name: $data);
    }

    /**
     * @param AlterTable $table
     * @param $data
     * @return string
     */
    protected function handleRenameColumn(AlterTable $table, $data): string
    {
        return '';
    }

    /**
     * @param AlterTable $table
     * @param $data
     * @return string
     */
    protected function handleModifyColumn(AlterTable $table, $data): string
    {
        return 'ALTER TABLE ' . $this->wrap(name: $table->getTableName()) . ' MODIFY COLUMN ' . $this->handleColumns(columns: [$data]);
    }

    /**
     * @param AlterTable $table
     * @param $data
     * @return string
     */
    protected function handleAddColumn(AlterTable $table, $data): string
    {
        return 'ALTER TABLE ' . $this->wrap(name: $table->getTableName()) . ' ADD COLUMN ' . $this->handleColumns(columns: [$data]);
    }

    /**
     * @param AlterTable $table
     * @param $data
     * @return string
     */
    protected function handleAddPrimary(AlterTable $table, $data): string
    {
        return 'ALTER TABLE ' . $this->wrap(name: $table->getTableName()) . ' ADD CONSTRAINT '
        . $this->wrap(name: $data['name']) . ' PRIMARY KEY (' . $this->wrapArray(value: $data['columns']) . ')';
    }

    /**
     * @param AlterTable $table
     * @param $data
     * @return string
     */
    protected function handleAddUnique(AlterTable $table, $data): string
    {
        return 'ALTER TABLE ' . $this->wrap(name: $table->getTableName()) . ' ADD CONSTRAINT '
        . $this->wrap(name: $data['name']) . ' UNIQUE (' . $this->wrapArray(value: $data['columns']) . ')';
    }

    /**
     * @param AlterTable $table
     * @param $data
     * @return string
     */
    protected function handleAddIndex(AlterTable $table, $data): string
    {
        return 'CREATE INDEX ' . $this->wrap(name: $data['name']) . ' ON ' . $this->wrap(name: $table->getTableName()) . ' (' . $this->wrapArray(value: $data['columns']) . ')';
    }

    /**
     * @param AlterTable $table
     * @param $data
     * @return string
     */
    protected function handleAddForeign(AlterTable $table, $data): string
    {
        /** @var ForeignKey $key */
        $key = $data['foreign'];
        return 'ALTER TABLE ' . $this->wrap(name: $table->getTableName()) . ' ADD CONSTRAINT '
        . $this->wrap(name: $data['name']) . ' FOREIGN KEY (' . $this->wrapArray(value: $key->getColumns()) . ') '
        . 'REFERENCES ' . $this->wrap(name: $key->getReferencedTable()) . '(' . $this->wrapArray(value: $key->getReferencedColumns()) . ')';
    }

    /**
     * @param AlterTable $table
     * @param $data
     * @return string
     */
    protected function handleSetDefaultValue(AlterTable $table, $data): string
    {
        return 'ALTER TABLE ' . $this->wrap(name: $table->getTableName()) . ' ALTER COLUMN '
        . $this->wrap(name: $data['column']) . ' SET DEFAULT ' . $this->value(value: $data['value']);
    }

    /**
     * @param AlterTable $table
     * @param $data
     * @return string
     */
    protected function handleDropDefaultValue(AlterTable $table, $data): string
    {
        return 'ALTER TABLE ' . $this->wrap(name: $table->getTableName()) . ' ALTER COLUMN '
        . $this->wrap(name: $data) . ' DROP DEFAULT';
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        $params = $this->params;
        $this->params = [];
        return $params;
    }

    /**
     * @param string $dsn
     * @return array
     */
    public function currentDatabase(string $dsn): array
    {
        return [
            'sql'    => 'SELECT database()',
            'params' => [],
        ];
    }

    /**
     * @param string $current
     * @param string $new
     * @return array
     */
    public function renameTable(string $current, string $new): array
    {
        return [
            'sql'    => 'RENAME TABLE ' . $this->wrap(name: $current) . ' TO ' . $this->wrap(name: $new),
            'params' => [],
        ];
    }

    /**
     * @param string $database
     * @return array
     */
    public function getTables(string $database): array
    {
        $sql = 'SELECT ' . $this->wrap(name: 'table_name') . ' FROM ' . $this->wrap(name: 'information_schema')
        . '.' . $this->wrap(name: 'tables') . ' WHERE table_type = ? AND table_schema = ? ORDER BY '
        . $this->wrap(name: 'table_name') . ' ASC';

        return [
            'sql'    => $sql,
            'params' => ['BASE TABLE', $database],
        ];
    }

    /**
     * @param string $database
     * @param string $table
     * @return array
     */
    public function getColumns(string $database, string $table): array
    {
        $sql = 'SELECT ' . $this->wrap(name: 'column_name') . ' AS ' . $this->wrap(name: 'name')
        . ', ' . $this->wrap(name: 'column_type') . ' AS ' . $this->wrap(name: 'type')
        . ' FROM ' . $this->wrap(name: 'information_schema') . '.' . $this->wrap(name: 'columns')
        . ' WHERE ' . $this->wrap(name: 'table_schema') . ' = ? AND ' . $this->wrap(name: 'table_name') . ' = ? '
        . ' ORDER BY ' . $this->wrap(name: 'ordinal_position') . ' ASC';

        return [
            'sql'    => $sql,
            'params' => [$database, $table],
        ];
    }

    /**
     * @param CreateTable $schema
     * @return array
     */
    public function create(CreateTable $schema): array
    {
        $sql = 'CREATE TABLE ' . $this->wrap(name: $schema->getTableName());
        $sql .= "(\n";
        $sql .= $this->handleColumns(columns: $schema->getColumns());
        $sql .= $this->handlePrimaryKey(schema: $schema);
        $sql .= $this->handleUniqueKeys(schema: $schema);
        $sql .= $this->handleForeignKeys(schema: $schema);
        $sql .= "\n)" . $this->handleEngine(schema: $schema);

        $commands = [];

        $commands[] = [
            'sql'    => $sql,
            'params' => $this->getParams(),
        ];

        foreach ($this->handleIndexKeys(schema: $schema) as $index) {
            $commands[] = [
                'sql'    => $index,
                'params' => [],
            ];
        }

        return $commands;
    }

    /**
     * @param AlterTable $schema
     * @return array
     */
    public function alter(AlterTable $schema): array
    {
        $commands = [];

        foreach ($schema->getCommands() as $command) {
            $type = 'handle' . ucfirst(string: $command['type']);
            $sql = $this->{$type}($schema, $command['data']);

            if ($sql === '') {
                continue;
            }

            $commands[] = [
                'sql'    => $sql,
                'params' => $this->getParams(),
            ];
        }

        return $commands;
    }

    /**
     * @param string $table
     * @return array
     */
    public function drop(string $table): array
    {
        return [
            'sql'    => 'DROP TABLE ' . $this->wrap(name: $table),
            'params' => [],
        ];
    }

    /**
     * @param string $table
     * @return array
     */
    public function truncate(string $table): array
    {
        return [
            'sql'    => 'TRUNCATE TABLE ' . $this->wrap(name: $table),
            'params' => [],
        ];
    }
}
