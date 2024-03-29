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

namespace Qubus\Dbal\Schema\Compiler;

use Qubus\Dbal\Schema\AlterTable;
use Qubus\Dbal\Schema\BaseColumn;
use Qubus\Dbal\Schema\Compiler;
use Qubus\Dbal\Schema\CreateTable;

class SQLServer extends Compiler
{
    protected string $wrapper = '[%s]';

    /** @var string[] $modifiers */
    protected array $modifiers = ['nullable', 'default', 'autoincrement'];

    /** @var string $autoincrement */
    protected string $autoincrement = 'IDENTITY';

    protected function handleTypeInteger(BaseColumn $column): string
    {
        return match ($column->get(name: 'size', default: 'normal')) {
            'tiny' => 'TINYINT',
            'small' => 'SMALLINT',
            'medium' => 'INTEGER',
            'big' => 'BIGINT',
            default => 'INTEGER',
        };
    }

    /**
     * @inheritDoc
     */
    protected function handleTypeDecimal(BaseColumn $column): string
    {
        if (null !== $l = $column->get(name: 'length')) {
            if (null === $p = $column->get(name: 'precision')) {
                return 'DECIMAL (' . $this->value($l) . ')';
            }
            return 'DECIMAL (' . $this->value($l) . ', ' . $this->value($p) . ')';
        }
        return 'DECIMAL';
    }

    /**
     * @inheritDoc
     */
    protected function handleTypeBoolean(BaseColumn $column): string
    {
        return 'BIT';
    }

    /**
     * @inheritDoc
     */
    protected function handleTypeString(BaseColumn $column): string
    {
        return 'NVARCHAR(' . $this->value($column->get('length', 255)) . ')';
    }

    /**
     * @inheritDoc
     */
    protected function handleTypeFixed(BaseColumn $column): string
    {
        return 'NCHAR(' . $this->value($column->get('length', 255)) . ')';
    }

    /**
     * @inheritDoc
     */
    protected function handleTypeText(BaseColumn $column): string
    {
        return 'NVARCHAR(max)';
    }

    /**
     * @inheritDoc
     */
    protected function handleTypeBinary(BaseColumn $column): string
    {
        return 'VARBINARY(max)';
    }

    /**
     * @inheritDoc
     */
    protected function handleTypeTimestamp(BaseColumn $column): string
    {
        return 'DATETIME';
    }

    /**
     * @inheritDoc
     */
    protected function handleRenameColumn(AlterTable $table, $data): string
    {
        /** @var BaseColumn $column */
        $column = $data['column'];
        return 'sp_rename ' . $this->wrap($table->getTableName()) . '.' . $this->wrap($data['from']) . ', '
        . $this->wrap($column->getName()) . ', COLUMN';
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
    public function renameTable(string $current, string $new): array
    {
        return [
            'sql'    => 'sp_rename ' . $this->wrap($current) . ', ' . $this->wrap($new),
            'params' => [],
        ];
    }

    /**
     * @inheritDoc
     */
    public function currentDatabase(string $dsn): array
    {
        return [
            'sql'    => 'SELECT SCHEMA_NAME()',
            'params' => [],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getColumns(string $database, string $table): array
    {
        $sql = 'SELECT ' . $this->wrap(name: 'column_name') . ' AS ' . $this->wrap(name: 'name')
        . ', ' . $this->wrap(name: 'data_type') . ' AS ' . $this->wrap(name: 'type')
        . ' FROM ' . $this->wrap(name: 'information_schema') . '.' . $this->wrap(name: 'columns')
        . ' WHERE ' . $this->wrap(name: 'table_schema') . ' = ? AND ' . $this->wrap(name: 'table_name') . ' = ? '
        . ' ORDER BY ' . $this->wrap(name: 'ordinal_position') . ' ASC';

        return [
            'sql'    => $sql,
            'params' => [$database, $table],
        ];
    }
}
