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

class Oracle extends Compiler
{
    /** @var string $autoincrement */
    protected string $autoincrement = 'GENERATED BY DEFAULT ON NULL AS IDENTITY';

    /** @var string[] $modifiers */
    protected array $modifiers = ['default', 'nullable', 'autoincrement'];

    /**
     * @inheritDoc
     */
    protected function handleTypeInteger(BaseColumn $column): string
    {
        switch ($column->get('size', 'normal')) {
            case 'tiny':
                return 'NUMBER(3)';
            case 'small':
                return 'NUMBER(5)';
            case 'medium':
                return 'NUMBER(7)';
            case 'big':
                return 'NUMBER(19)';
        }

        return 'NUMBER(10)';
    }

    /**
     * @inheritDoc
     */
    protected function handleTypeDouble(BaseColumn $column): string
    {
        return 'FLOAT(24)';
    }

    /**
     * @inheritDoc
     */
    protected function handleTypeDecimal(BaseColumn $column): string
    {
        if (null !== $l = $column->get('length')) {
            if (null === $p = $column->get('precision')) {
                return 'NUMBER(' . $this->value($l) . ')';
            }
            return 'NUMBER(' . $this->value($l) . ', ' . $this->value($p) . ')';
        }

        return 'NUMBER(10)';
    }

    /**
     * @inheritDoc
     */
    protected function handleTypeBoolean(BaseColumn $column): string
    {
        return 'NUMBER(1)';
    }

    /**
     * @inheritDoc
     */
    protected function handleTypeText(BaseColumn $column): string
    {
        switch ($column->get('size', 'normal')) {
            case 'tiny':
            case 'small':
                return 'VARCHAR2(2000)';
            case 'medium':
            case 'big':
                return 'CLOB';
        }

        return 'CLOB';
    }

    /**
     * @inheritDoc
     */
    protected function handleTypeString(BaseColumn $column): string
    {
        return 'VARCHAR2(' . $this->value($column->get('length', 255)) . ')';
    }

    /**
     * @inheritDoc
     */
    protected function handleTypeTime(BaseColumn $column): string
    {
        return 'DATE';
    }

    /**
     * @inheritDoc
     */
    protected function handleTypeDateTime(BaseColumn $column): string
    {
        return 'DATE';
    }

    /**
     * @inheritDoc
     */
    protected function handleTypeBinary(BaseColumn $column): string
    {
        switch ($column->get('size', 'normal')) {
            case 'tiny':
            case 'small':
                return 'RAW(2000)';
            case 'medium':
            case 'large':
                return 'BLOB';
        }

        return 'BLOB';
    }

    /**
     * @inheritDoc
     */
    protected function handleModifyColumn(AlterTable $table, $data): string
    {
        return 'ALTER TABLE ' . $this->wrap($table->getTableName()) . ' MODIFY ' . $this->handleColumns([$data]);
    }

    /**
     * @inheritDoc
     */
    protected function handleAddColumn(AlterTable $table, $data): string
    {
        return 'ALTER TABLE ' . $this->wrap($table->getTableName()) . ' ADD ' . $this->handleColumns([$data]);
    }

    /**
     * @inheritDoc
     */
    protected function handleSetDefaultValue(AlterTable $table, $data): string
    {
        return 'ALTER TABLE ' . $this->wrap($table->getTableName()) . ' MODIFY '
        . $this->wrap($data) . ' DEFAULT ' . $this->value($data['value']);
    }

    /**
     * @inheritDoc
     */
    protected function handleDropDefaultValue(AlterTable $table, $data): string
    {
        return 'ALTER TABLE ' . $this->wrap($table->getTableName()) . ' MODIFY '
        . $this->wrap($data) . ' DEFAULT NULL';
    }

    /**
     * @inheritDoc
     */
    public function currentDatabase(string $dsn): array
    {
        return [
            'sql'    => 'SELECT user FROM dual',
            'params' => [],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getTables(string $database): array
    {
        $sql = 'SELECT ' . $this->wrap('table_name') . ' FROM ' . $this->wrap('all_tables')
        . ' WHERE owner = ? '
        . ' ORDER BY ' . $this->wrap('table_name') . ' ASC';

        return [
            'sql'    => $sql,
            'params' => [$database],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getColumns(string $database, string $table): array
    {
        $sql = 'SELECT ' . $this->wrap('column_name') . ' AS ' . $this->wrap('name')
        . ', ' . $this->wrap('data_type') . ' AS ' . $this->wrap('type')
        . ' FROM ' . $this->wrap('all_tab_columns')
        . ' WHERE LOWER(' . $this->wrap('owner') . ') = ? AND LOWER(' . $this->wrap('table_name') . ') = ? '
        . ' ORDER BY ' . $this->wrap('column_id') . ' ASC';

        return [
            'sql'    => $sql,
            'params' => [$database, $table],
        ];
    }
}
