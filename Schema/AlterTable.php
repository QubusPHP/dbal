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

namespace Qubus\Dbal\Schema;

use function implode;
use function is_array;

class AlterTable
{
    protected string $table;

    /** @var array $commands */
    protected array $commands = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    /**
     * @param $data
     * @return $this
     */
    protected function addCommand(string $name, $data): self
    {
        $this->commands[] = [
            'type' => $name,
            'data' => $data,
        ];

        return $this;
    }

    /**
     * @param string|string[] $columns
     * @return $this
     */
    protected function addKey(string $type, $columns, ?string $name = null): self
    {
        static $map = [
            'addPrimary'    => 'pk',
            'addUnique'     => 'uk',
            'addForeignKey' => 'fk',
            'addIndex'      => 'ik',
        ];

        if (! is_array($columns)) {
            $columns = [$columns];
        }

        if ($name === null) {
            $name = $this->table . '_' . $map[$type] . '_' . implode('_', $columns);
        }

        return $this->addCommand($type, [
            'name'    => $name,
            'columns' => $columns,
        ]);
    }

    protected function addColumn(string $name, string $type): AlterColumn
    {
        $columnObject = new AlterColumn($this, $name, $type);
        $this->addCommand('addColumn', $columnObject);
        return $columnObject;
    }

    protected function modifyColumn(string $column, string $type): AlterColumn
    {
        $columnObject = new AlterColumn($this, $column, $type);
        $columnObject->set('handleDefault', false);
        $this->addCommand('modifyColumn', $columnObject);
        return $columnObject;
    }

    public function getTableName(): string
    {
        return $this->table;
    }

    /**
     * @return array
     */
    public function getCommands(): array
    {
        return $this->commands;
    }

    /**
     * @return $this
     */
    public function dropIndex(string $name): self
    {
        return $this->addCommand('dropIndex', $name);
    }

    /**
     * @return $this
     */
    public function dropUnique(string $name): self
    {
        return $this->addCommand('dropUniqueKey', $name);
    }

    /**
     * @return $this
     */
    public function dropPrimary(string $name): self
    {
        return $this->addCommand('dropPrimaryKey', $name);
    }

    /**
     * @return $this
     */
    public function dropForeign(string $name): self
    {
        return $this->addCommand('dropForeignKey', $name);
    }

    /**
     * @return $this
     */
    public function dropColumn(string $name): self
    {
        return $this->addCommand('dropColumn', $name);
    }

    /**
     * @return $this
     */
    public function dropDefaultValue(string $column): self
    {
        return $this->addCommand('dropDefaultValue', $column);
    }

    /**
     * @return $this
     */
    public function renameColumn(string $from, string $to): self
    {
        return $this->addCommand('renameColumn', [
            'from'   => $from,
            'column' => new AlterColumn($this, $to),
        ]);
    }

    /**
     * @param string|string[] $columns
     * @return $this
     */
    public function primary($columns, ?string $name = null): self
    {
        return $this->addKey('addPrimary', $columns, $name);
    }

    /**
     * @param string|string[] $columns
     * @return $this
     */
    public function unique($columns, ?string $name = null): self
    {
        return $this->addKey('addUnique', $columns, $name);
    }

    /**
     * @param string|string[] $columns
     * @return $this
     */
    public function index($columns, ?string $name = null): self
    {
        return $this->addKey('addIndex', $columns, $name);
    }

    /**
     * @param string|string[] $columns
     */
    public function foreign($columns, ?string $name = null): ForeignKey
    {
        if (! is_array($columns)) {
            $columns = [$columns];
        }

        if ($name === null) {
            $name = $this->table . '_fk_' . implode('_', $columns);
        }

        $foreign = new ForeignKey($columns);

        $this->addCommand('addForeign', [
            'name'    => $name,
            'foreign' => $foreign,
        ]);

        return $foreign;
    }

    /**
     * @param $value
     * @return $this
     */
    public function setDefaultValue(string $column, $value): self
    {
        return $this->addCommand('setDefaultValue', [
            'column' => $column,
            'value'  => $value,
        ]);
    }

    public function integer(string $name): AlterColumn
    {
        return $this->addColumn($name, 'integer');
    }

    public function float(string $name): AlterColumn
    {
        return $this->addColumn($name, 'float');
    }

    public function double(string $name): AlterColumn
    {
        return $this->addColumn($name, 'double');
    }

    public function decimal(string $name, ?int $length = null, ?int $precision = null): AlterColumn
    {
        return $this->addColumn($name, 'decimal')
            ->set('length', $length)
            ->set('precision', $precision);
    }

    public function boolean(string $name): AlterColumn
    {
        return $this->addColumn($name, 'boolean');
    }

    public function binary(string $name): AlterColumn
    {
        return $this->addColumn($name, 'binary');
    }

    public function string(string $name, int $length = 255): AlterColumn
    {
        return $this->addColumn($name, 'string')->set('length', $length);
    }

    public function fixed(string $name, int $length = 255): AlterColumn
    {
        return $this->addColumn($name, 'fixed')->set('length', $length);
    }

    public function text(string $name): AlterColumn
    {
        return $this->addColumn($name, 'text');
    }

    public function time(string $name): AlterColumn
    {
        return $this->addColumn($name, 'time');
    }

    public function timestamp(string $name): AlterColumn
    {
        return $this->addColumn($name, 'timestamp');
    }

    public function date(string $name): AlterColumn
    {
        return $this->addColumn($name, 'date');
    }

    public function dateTime(string $name): AlterColumn
    {
        return $this->addColumn($name, 'dateTime');
    }

    public function toInteger(string $name): AlterColumn
    {
        return $this->modifyColumn($name, 'integer');
    }

    public function toFloat(string $name): AlterColumn
    {
        return $this->modifyColumn($name, 'float');
    }

    public function toDouble(string $name): AlterColumn
    {
        return $this->modifyColumn($name, 'double');
    }

    public function toDecimal(string $name, ?int $length = null, ?int $precision = null): AlterColumn
    {
        return $this->modifyColumn($name, 'decimal')
            ->set('length', $length)
            ->set('precision', $precision);
    }

    public function toBoolean(string $name): AlterColumn
    {
        return $this->modifyColumn($name, 'boolean');
    }

    public function toBinary(string $name): AlterColumn
    {
        return $this->modifyColumn($name, 'binary');
    }

    public function toString(string $name, int $length = 255): AlterColumn
    {
        return $this->modifyColumn($name, 'string')->set('length', $length);
    }

    public function toFixed(string $name, int $length = 255): AlterColumn
    {
        return $this->modifyColumn($name, 'fixed')->set('length', $length);
    }

    public function toText(string $name): AlterColumn
    {
        return $this->modifyColumn($name, 'text');
    }

    public function toTime(string $name): AlterColumn
    {
        return $this->modifyColumn($name, 'time');
    }

    public function toTimestamp(string $name): AlterColumn
    {
        return $this->modifyColumn($name, 'timestamp');
    }

    public function toDate(string $name): AlterColumn
    {
        return $this->modifyColumn($name, 'date');
    }

    public function toDateTime(string $name): AlterColumn
    {
        return $this->modifyColumn($name, 'dateTime');
    }
}
