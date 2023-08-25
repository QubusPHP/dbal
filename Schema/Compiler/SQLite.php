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

use function strpos;
use function substr;

class SQLite extends Compiler
{
    /** @var string[] $modifiers */
    protected array $modifiers = ['nullable', 'default', 'autoincrement'];

    /** @var string $autoincrement */
    protected string $autoincrement = 'AUTOINCREMENT';

    /** @var bool $nopk No primary key */
    private bool $nopk = false;

    protected function handleTypeInteger(BaseColumn $column): string
    {
        return 'INTEGER';
    }

    protected function handleTypeTime(BaseColumn $column): string
    {
        return 'DATETIME';
    }

    protected function handleTypeTimestamp(BaseColumn $column): string
    {
        return 'DATETIME';
    }

    public function handleModifierAutoincrement(BaseColumn $column): string
    {
        $modifier = parent::handleModifierAutoincrement(column: $column);

        if ($modifier !== '') {
            $this->nopk = true;
            $modifier = 'PRIMARY KEY ' . $modifier;
        }

        return $modifier;
    }

    public function handlePrimaryKey(CreateTable $schema): string
    {
        if ($this->nopk) {
            return '';
        }

        return parent::handlePrimaryKey(schema: $schema);
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
        return 'CREATE UNIQUE INDEX ' . $this->wrap(name: $data['name']) . ' ON '
        . $this->wrap(name: $table->getTableName()) . '(' . $this->wrapArray(value: $data['columns']) . ')';
    }

    /**
     * @inheritDoc
     */
    protected function handleAddIndex(AlterTable $table, $data): string
    {
        return 'CREATE INDEX ' . $this->wrap(name: $data['name']) . ' ON '
        . $this->wrap(name: $table->getTableName()) . '(' . $this->wrapArray(value: $data['columns']) . ')';
    }

    /**
     * @inheritDoc
     */
    public function currentDatabase(string $dsn): array
    {
        return [
            'result' => substr(string: $dsn, offset: strpos(haystack: $dsn, needle: ':') + 1),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getTables(string $database): array
    {
        $sql = 'SELECT ' . $this->wrap(name: 'name') . ' FROM ' . $this->wrap(name: 'sqlite_master')
        . ' WHERE type = ? ORDER BY ' . $this->wrap(name: 'name') . ' ASC';

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
            'sql'    => 'PRAGMA table_info(' . $this->wrap(name: $table) . ')',
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
