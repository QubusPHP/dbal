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

use function strpos;
use function substr;

class SQLite extends Compiler
{
    /** @var string[] $modifiers */
    protected $modifiers = ['nullable', 'default', 'autoincrement'];

    /** @var string $autoincrement */
    protected $autoincrement = 'AUTOINCREMENT';

    /** @var bool $nopk No primary key */
    private bool $nopk = false;

    /**
     * @inheritDoc
     */
    protected function handleTypeInteger(BaseColumn $column): string
    {
        return 'INTEGER';
    }

    /**
     * @inheritDoc
     */
    protected function handleTypeTime(BaseColumn $column): string
    {
        return 'DATETIME';
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
    public function handleModifierAutoincrement(BaseColumn $column): string
    {
        $modifier = parent::handleModifierAutoincrement($column);

        if ($modifier !== '') {
            $this->nopk = true;
            $modifier = 'PRIMARY KEY ' . $modifier;
        }

        return $modifier;
    }

    /**
     * @inheritDoc
     */
    public function handlePrimaryKey(CreateTable $schema): string
    {
        if ($this->nopk) {
            return '';
        }

        return parent::handlePrimaryKey($schema);
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
    protected function handleAddUnique(AlterTable $table, $data): string
    {
        return 'CREATE UNIQUE INDEX ' . $this->wrap($data['name']) . ' ON '
        . $this->wrap($table->getTableName()) . '(' . $this->wrapArray($data['columns']) . ')';
    }

    /**
     * @inheritDoc
     */
    protected function handleAddIndex(AlterTable $table, $data): string
    {
        return 'CREATE INDEX ' . $this->wrap($data['name']) . ' ON '
        . $this->wrap($table->getTableName()) . '(' . $this->wrapArray($data['columns']) . ')';
    }

    /**
     * @inheritDoc
     */
    public function currentDatabase(string $dsn): array
    {
        return [
            'result' => substr($dsn, strpos($dsn, ':') + 1),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getTables(string $database): array
    {
        $sql = 'SELECT ' . $this->wrap('name') . ' FROM ' . $this->wrap('sqlite_master')
        . ' WHERE type = ? ORDER BY ' . $this->wrap('name') . ' ASC';

        return [
            'sql'    => $sql,
            'params' => ['table'],
        ];
    }

    /**
     * @inheritDoc
     */
    public function getColumns(string $database, string $table): array
    {
        return [
            'sql'    => 'PRAGMA table_info(' . $this->wrap($table) . ')',
            'params' => [],
        ];
    }

    /**
     * @inheritDoc
     */
    public function renameTable(string $current, string $new): array
    {
        return [
            'sql'    => 'ALTER TABLE ' . $this->wrap($current) . ' RENAME TO ' . $this->wrap($new),
            'params' => [],
        ];
    }
}
