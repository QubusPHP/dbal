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

namespace Qubus\Dbal\Schema\Compiler;

use Qubus\Dbal\Schema\AlterTable;
use Qubus\Dbal\Schema\BaseColumn;
use Qubus\Dbal\Schema\Compiler;
use Qubus\Dbal\Schema\CreateTable;

class PostgreSQL extends Compiler
{
    /** @var string[] $modifiers */
    protected array $modifiers = ['nullable', 'default'];

    protected function handleTypeInteger(BaseColumn $column): string
    {
        $autoincrement = $column->get(name: 'autoincrement', default: false);

        return match($column->get(name: 'size', default: 'normal')) {
            'tiny', 'small' => $autoincrement ? 'SMALLSERIAL' : 'SMALLINT',
            'medium' => $autoincrement ? 'SERIAL' : 'INTEGER',
            'big' => $autoincrement ? 'BIGSERIAL' : 'BIGINT',
            default => $autoincrement ? 'SERIAL' : 'INTEGER',
        };
    }

    protected function handleTypeFloat(BaseColumn $column): string
    {
        return 'REAL';
    }

    protected function handleTypeDouble(BaseColumn $column): string
    {
        return 'DOUBLE PRECISION';
    }

    protected function handleTypeDecimal(BaseColumn $column): string
    {
        if (null !== $l = $column->get(name: 'length')) {
            if (null === $p = $column->get(name: 'precision')) {
                return 'DECIMAL (' . $this->value(value: $l) . ')';
            }
            return 'DECIMAL (' . $this->value(value: $l) . ', ' . $this->value(value: $p) . ')';
        }
        return 'DECIMAL';
    }

    protected function handleTypeBinary(BaseColumn $column): string
    {
        return 'BYTEA';
    }

    protected function handleTypeTime(BaseColumn $column): string
    {
        return 'TIME(0) WITHOUT TIME ZONE';
    }

    protected function handleTypeTimestamp(BaseColumn $column): string
    {
        return 'TIMESTAMP(0) WITHOUT TIME ZONE';
    }

    protected function handleTypeDateTime(BaseColumn $column): string
    {
        return 'TIMESTAMP(0) WITHOUT TIME ZONE';
    }

    /**
     * @inheritDoc
     */
    protected function handleIndexKeys(CreateTable $schema): array
    {
        $indexes = $schema->getIndexes();

        if (empty($indexes)) {
            return [];
        }

        $sql = [];

        $table = $schema->getTableName();

        foreach ($indexes as $name => $columns) {
            $sql[] = 'CREATE INDEX ' . $this->wrap(name: $table . '_' . $name) . ' ON ' . $this->wrap(name: $table) . '(' . $this->wrapArray(value: $columns) . ')';
        }

        return $sql;
    }

    /**
     * @inheritDoc
     */
    protected function handleRenameColumn(AlterTable $table, $data): string
    {
        /** @var BaseColumn $column */
        $column = $data['column'];
        return 'ALTER TABLE ' . $this->wrap(name: $table->getTableName()) . ' RENAME COLUMN '
        . $this->wrap(name: $data['from']) . ' TO ' . $this->wrap(name: $column->getName());
    }

    /**
     * @inheritDoc
     */
    protected function handleAddIndex(AlterTable $table, $data): string
    {
        return 'CREATE INDEX ' . $this->wrap(name: $table->getTableName() . '_' . $data['name']) . ' ON ' . $this->wrap(name: $table->getTableName()) . ' (' . $this->wrapArray(value: $data['columns']) . ')';
    }

    /**
     * @inheritDoc
     */
    protected function handleDropIndex(AlterTable $table, $data): string
    {
        return 'DROP INDEX ' . $this->wrap(name: $table->getTableName() . '_' . $data);
    }

    /**
     * @inheritDoc
     */
    protected function handleEngine(CreateTable $schema): string
    {
        return '';
    }

    /**
     * @inheritDoc
     */
    public function getColumns(string $database, string $table): array
    {
        $sql = 'SELECT ' . $this->wrap(name: 'column_name') . ' AS ' . $this->wrap(name: 'name')
        . ', ' . $this->wrap(name: 'udt_name') . ' AS ' . $this->wrap(name: 'type')
        . ' FROM ' . $this->wrap(name: 'information_schema') . '.' . $this->wrap(name: 'columns')
        . ' WHERE ' . $this->wrap(name: 'table_schema') . ' = ? AND ' . $this->wrap(name: 'table_name') . ' = ? '
        . ' ORDER BY ' . $this->wrap(name: 'ordinal_position') . ' ASC';

        return [
            'sql'    => $sql,
            'params' => [$database, $table],
        ];
    }

    /**
     * @inheritDoc
     */
    public function currentDatabase(string $dsn): array
    {
        return [
            'sql'    => 'SELECT current_schema()',
            'params' => [],
        ];
    }

    /**
     * @inheritDoc
     */
    public function renameTable(string $current, string $new): array
    {
        return [
            'sql'    => 'ALTER TABLE ' . $this->wrap(name: $current) . ' RENAME TO ' . $this->wrap(name: $new),
            'params' => [],
        ];
    }
}
