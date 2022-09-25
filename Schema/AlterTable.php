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
use function Qubus\Support\Helpers\is_null__;

class AlterTable
{
    protected ?string $table = null;

    /** @var array $commands */
    protected array $commands = [];

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    /**
     * @param string $name
     * @param mixed $data
     * @return $this
     */
    protected function addCommand(string $name, mixed $data): self
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
    protected function addKey(string $type, array|string $columns, ?string $name = null): self
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

        if (is_null__($name)) {
            $name = $this->table . '_' . $map[$type] . '_' . implode(separator: '_', array: $columns);
        }

        return $this->addCommand(name: $type, data: [
            'name'    => $name,
            'columns' => $columns,
        ]);
    }

    protected function addColumn(string $name, string $type): AlterColumn
    {
        $columnObject = new AlterColumn(table: $this, name: $name, type: $type);
        $this->addCommand(name: 'addColumn', data: $columnObject);
        return $columnObject;
    }

    protected function modifyColumn(string $column, string $type): AlterColumn
    {
        $columnObject = new AlterColumn(table: $this, name: $column, type: $type);
        $columnObject->set(name: 'handleDefault', value: false);
        $this->addCommand(name: 'modifyColumn', data: $columnObject);
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
        return $this->addCommand(name: 'dropIndex', data: $name);
    }

    /**
     * @return $this
     */
    public function dropUnique(string $name): self
    {
        return $this->addCommand(name: 'dropUniqueKey', data: $name);
    }

    /**
     * @return $this
     */
    public function dropPrimary(string $name): self
    {
        return $this->addCommand(name: 'dropPrimaryKey', data: $name);
    }

    /**
     * @return $this
     */
    public function dropForeign(string $name): self
    {
        return $this->addCommand(name: 'dropForeignKey', data: $name);
    }

    /**
     * @return $this
     */
    public function dropColumn(string $name): self
    {
        return $this->addCommand(name: 'dropColumn', data: $name);
    }

    /**
     * @return $this
     */
    public function dropDefaultValue(string $column): self
    {
        return $this->addCommand(name: 'dropDefaultValue', data: $column);
    }

    /**
     * @return $this
     */
    public function renameColumn(string $from, string $to): self
    {
        return $this->addCommand(name: 'renameColumn', data: [
            'from'   => $from,
            'column' => new AlterColumn($this, $to),
        ]);
    }

    /**
     * @param string|string[] $columns
     * @return $this
     */
    public function primary(array|string $columns, ?string $name = null): self
    {
        return $this->addKey(type: 'addPrimary', columns: $columns, name: $name);
    }

    /**
     * @param string|string[] $columns
     * @return $this
     */
    public function unique(array|string $columns, ?string $name = null): self
    {
        return $this->addKey(type: 'addUnique', columns: $columns, name: $name);
    }

    /**
     * @param string|string[] $columns
     * @return $this
     */
    public function index(array|string $columns, ?string $name = null): self
    {
        return $this->addKey(type: 'addIndex', columns: $columns, name: $name);
    }

    /**
     * @param string|string[] $columns
     */
    public function foreign(array|string $columns, ?string $name = null): ForeignKey
    {
        if (! is_array(value: $columns)) {
            $columns = [$columns];
        }

        if (is_null__($name)) {
            $name = $this->table . '_fk_' . implode(separator: '_', array: $columns);
        }

        $foreign = new ForeignKey(columns: $columns);

        $this->addCommand(name: 'addForeign', data: [
            'name'    => $name,
            'foreign' => $foreign,
        ]);

        return $foreign;
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function setDefaultValue(string $column, mixed $value): self
    {
        return $this->addCommand(name: 'setDefaultValue', data: [
            'column' => $column,
            'value'  => $value,
        ]);
    }

    public function integer(string $name): AlterColumn
    {
        return $this->addColumn(name: $name, type: 'integer');
    }

    public function float(string $name): AlterColumn
    {
        return $this->addColumn(name: $name, type: 'float');
    }

    public function double(string $name): AlterColumn
    {
        return $this->addColumn(name: $name, type: 'double');
    }

    public function decimal(string $name, ?int $length = null, ?int $precision = null): AlterColumn
    {
        return $this->addColumn(name: $name, type: 'decimal')
            ->set(name: 'length', value: $length)
            ->set(name: 'precision', value: $precision);
    }

    public function boolean(string $name): AlterColumn
    {
        return $this->addColumn(name: $name, type: 'boolean');
    }

    public function binary(string $name): AlterColumn
    {
        return $this->addColumn(name: $name, type: 'binary');
    }

    public function string(string $name, int $length = 255): AlterColumn
    {
        return $this->addColumn(name: $name, type: 'string')->set(name: 'length', value: $length);
    }

    public function fixed(string $name, int $length = 255): AlterColumn
    {
        return $this->addColumn(name: $name, type: 'fixed')->set(name: 'length', value: $length);
    }

    public function text(string $name): AlterColumn
    {
        return $this->addColumn(name: $name, type: 'text');
    }

    public function time(string $name): AlterColumn
    {
        return $this->addColumn(name: $name, type: 'time');
    }

    public function timestamp(string $name): AlterColumn
    {
        return $this->addColumn(name: $name, type: 'timestamp');
    }

    public function date(string $name): AlterColumn
    {
        return $this->addColumn(name: $name, type: 'date');
    }

    public function dateTime(string $name): AlterColumn
    {
        return $this->addColumn(name: $name, type: 'dateTime');
    }

    public function toInteger(string $name): AlterColumn
    {
        return $this->modifyColumn(column: $name, type: 'integer');
    }

    public function toFloat(string $name): AlterColumn
    {
        return $this->modifyColumn(column: $name, type: 'float');
    }

    public function toDouble(string $name): AlterColumn
    {
        return $this->modifyColumn(column: $name, type: 'double');
    }

    public function toDecimal(string $name, ?int $length = null, ?int $precision = null): AlterColumn
    {
        return $this->modifyColumn(column: $name, type: 'decimal')
            ->set(name: 'length', value: $length)
            ->set(name: 'precision', value: $precision);
    }

    public function toBoolean(string $name): AlterColumn
    {
        return $this->modifyColumn(column: $name, type: 'boolean');
    }

    public function toBinary(string $name): AlterColumn
    {
        return $this->modifyColumn(column: $name, type: 'binary');
    }

    public function toString(string $name, int $length = 255): AlterColumn
    {
        return $this->modifyColumn(column: $name, type: 'string')->set(name: 'length', value: $length);
    }

    public function toFixed(string $name, int $length = 255): AlterColumn
    {
        return $this->modifyColumn(column: $name, type: 'fixed')->set(name: 'length', value: $length);
    }

    public function toText(string $name): AlterColumn
    {
        return $this->modifyColumn(column: $name, type: 'text');
    }

    public function toTime(string $name): AlterColumn
    {
        return $this->modifyColumn(column: $name, type: 'time');
    }

    public function toTimestamp(string $name): AlterColumn
    {
        return $this->modifyColumn(column: $name, type: 'timestamp');
    }

    public function toDate(string $name): AlterColumn
    {
        return $this->modifyColumn(column: $name, type: 'date');
    }

    public function toDateTime(string $name): AlterColumn
    {
        return $this->modifyColumn(column: $name, type: 'dateTime');
    }
}
