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
use Qubus\Exception\Exception;

class MySQL extends Compiler
{
    protected string $wrapper = '`%s`';

    /**
     * @inheritDoc
     */
    protected function handleTypeInteger(BaseColumn $column): string
    {
        switch ($column->get('size', 'normal')) {
            case 'tiny':
                return 'TINYINT';
            case 'small':
                return 'SMALLINT';
            case 'medium':
                return 'MEDIUMINT';
            case 'big':
                return 'BIGINT';
        }

        return 'INT';
    }

    /**
     * @inheritDoc
     */
    protected function handleTypeDecimal(BaseColumn $column): string
    {
        if (null !== $l = $column->get('length')) {
            if (null === $p = $column->get('precision')) {
                return 'DECIMAL(' . $this->value($l) . ')';
            }
            return 'DECIMAL(' . $this->value($l) . ', ' . $this->value($p) . ')';
        }
        return 'DECIMAL';
    }

    /**
     * @inheritDoc
     */
    protected function handleTypeBoolean(BaseColumn $column): string
    {
        return 'TINYINT(1)';
    }

    /**
     * @inheritDoc
     */
    protected function handleTypeText(BaseColumn $column): string
    {
        switch ($column->get('size', 'normal')) {
            case 'tiny':
            case 'small':
                return 'TINYTEXT';
            case 'medium':
                return 'MEDIUMTEXT';
            case 'big':
                return 'LONGTEXT';
        }

        return 'TEXT';
    }

    /**
     * @inheritDoc
     */
    protected function handleTypeBinary(BaseColumn $column): string
    {
        switch ($column->get('size', 'normal')) {
            case 'tiny':
            case 'small':
                return 'TINYBLOB';
            case 'medium':
                return 'MEDIUMBLOB';
            case 'big':
                return 'LONGBLOB';
        }

        return 'BLOB';
    }

    /**
     * @inheritDoc
     */
    protected function handleDropPrimaryKey(AlterTable $table, $data): string
    {
        return 'ALTER TABLE ' . $this->wrap($table->getTableName()) . ' DROP PRIMARY KEY';
    }

    /**
     * @inheritDoc
     */
    protected function handleDropUniqueKey(AlterTable $table, $data): string
    {
        return 'ALTER TABLE ' . $this->wrap($table->getTableName()) . ' DROP INDEX ' . $this->wrap($data);
    }

    /**
     * @inheritDoc
     */
    protected function handleDropIndex(AlterTable $table, $data): string
    {
        return 'ALTER TABLE ' . $this->wrap($table->getTableName()) . ' DROP INDEX ' . $this->wrap($data);
    }

    /**
     * @inheritDoc
     */
    protected function handleDropForeignKey(AlterTable $table, $data): string
    {
        return 'ALTER TABLE ' . $this->wrap($table->getTableName()) . ' DROP FOREIGN KEY ' . $this->wrap($data);
    }

    /**
     * @inheritDoc
     */
    protected function handleSetDefaultValue(AlterTable $table, $data): string
    {
        return 'ALTER TABLE ' . $this->wrap($table->getTableName()) . ' ALTER '
        . $this->wrap($data['column']) . ' SET DEFAULT ' . $this->value($data['value']);
    }

    /**
     * @inheritDoc
     */
    protected function handleDropDefaultValue(AlterTable $table, $data): string
    {
        return 'ALTER TABLE ' . $this->wrap($table->getTableName()) . ' ALTER ' . $this->wrap($data) . ' DROP DEFAULT';
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    protected function handleRenameColumn(AlterTable $table, $data): string
    {
        $tableName = $table->getTableName();
        $columnName = $data['from'];
        /** @var BaseColumn $column */
        $column = $data['column'];
        $newName = $column->getName();
        $columns = $this->connection->getSchema()->getColumns($tableName, false, false);
        $columnType = isset($columns[$columnName]) ? $columns[$columnName]['type'] : 'integer';

        return 'ALTER TABLE ' . $this->wrap($tableName) . ' CHANGE ' . $this->wrap($columnName)
        . ' ' . $this->wrap($newName) . ' ' . $columnType;
    }
}
