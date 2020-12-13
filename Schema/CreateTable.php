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

class CreateTable
{
    /** @var CreateColumn[] $columns */
    protected $columns = [];

    /** @var string|string[] $primaryKey */
    protected $primaryKey;

    /** @var string[] $uniqueKeys */
    protected $uniqueKeys = [];

    /** @var array $indexes */
    protected array $indexes = [];

    /** @var array $foreignKeys */
    protected array $foreignKeys = [];

    protected string $table;

    protected ?string $engine = null;

    /** @var bool|null $autoincrement */
    protected $autoincrement;

    public function __construct(string $table)
    {
        $this->table = $table;
    }

    protected function addColumn(string $name, string $type): CreateColumn
    {
        $column = new CreateColumn($this, $name, $type);
        $this->columns[$name] = $column;
        return $column;
    }

    public function getTableName(): string
    {
        return $this->table;
    }

    /**
     * @return CreateColumn[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return  mixed
     */
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    /**
     * @return  array
     */
    public function getUniqueKeys()
    {
        return $this->uniqueKeys;
    }

    /**
     * @return  array
     */
    public function getIndexes()
    {
        return $this->indexes;
    }

    /**
     * @return  array
     */
    public function getForeignKeys()
    {
        return $this->foreignKeys;
    }

    /**
     * @return  mixed
     */
    public function getEngine()
    {
        return $this->engine;
    }

    /**
     * @return  mixed
     */
    public function getAutoincrement()
    {
        return $this->autoincrement;
    }

    /**
     * @return $this
     */
    public function engine(string $name): self
    {
        $this->engine = $name;
        return $this;
    }

    /**
     * @param string|string[] $columns
     * @return $this
     */
    public function primary($columns, ?string $name = null): self
    {
        if (! is_array($columns)) {
            $columns = [$columns];
        }

        if ($name === null) {
            $name = $this->table . '_pk_' . implode('_', $columns);
        }

        $this->primaryKey = [
            'name'    => $name,
            'columns' => $columns,
        ];

        return $this;
    }

    /**
     * @param string|string[] $columns
     * @return $this
     */
    public function unique($columns, ?string $name = null): self
    {
        if (! is_array($columns)) {
            $columns = [$columns];
        }

        if ($name === null) {
            $name = $this->table . '_uk_' . implode('_', $columns);
        }

        $this->uniqueKeys[$name] = $columns;

        return $this;
    }

    /**
     * @param string|string[] $columns
     * @return $this
     */
    public function index($columns, ?string $name = null)
    {
        if (! is_array($columns)) {
            $columns = [$columns];
        }

        if ($name === null) {
            $name = $this->table . '_ik_' . implode('_', $columns);
        }

        $this->indexes[$name] = $columns;

        return $this;
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

        return $this->foreignKeys[$name] = new ForeignKey($columns);
    }

    /**
     * @return $this
     */
    public function autoincrement(CreateColumn $column, ?string $name = null): self
    {
        if ($column->getType() !== 'integer') {
            return $this;
        }

        $this->autoincrement = $column->set('autoincrement', true);
        return $this->primary($column->getName(), $name);
    }

    public function integer(string $name): CreateColumn
    {
        return $this->addColumn($name, 'integer');
    }

    public function float(string $name): CreateColumn
    {
        return $this->addColumn($name, 'float');
    }

    public function double(string $name): CreateColumn
    {
        return $this->addColumn($name, 'double');
    }

    public function decimal(string $name, ?int $length = null, ?int $precision = null): CreateColumn
    {
        return $this->addColumn($name, 'decimal')->length($length)->set('precision', $precision);
    }

    public function boolean(string $name): CreateColumn
    {
        return $this->addColumn($name, 'boolean');
    }

    public function binary(string $name): CreateColumn
    {
        return $this->addColumn($name, 'binary');
    }

    public function string(string $name, int $length = 255): CreateColumn
    {
        return $this->addColumn($name, 'string')->length($length);
    }

    public function fixed(string $name, int $length = 255): CreateColumn
    {
        return $this->addColumn($name, 'fixed')->length($length);
    }

    public function text(string $name): CreateColumn
    {
        return $this->addColumn($name, 'text');
    }

    public function time(string $name): CreateColumn
    {
        return $this->addColumn($name, 'time');
    }

    public function timestamp(string $name): CreateColumn
    {
        return $this->addColumn($name, 'timestamp');
    }

    public function date(string $name): CreateColumn
    {
        return $this->addColumn($name, 'date');
    }

    public function dateTime(string $name): CreateColumn
    {
        return $this->addColumn($name, 'dateTime');
    }

    /**
     * @return $this
     */
    public function softDelete(string $column = 'deleted_at'): self
    {
        $this->dateTime($column);
        return $this;
    }

    /**
     * @return $this
     */
    public function timestamps(string $createColumn = 'created_at', string $updateColumn = 'updated_at'): self
    {
        $this->dateTime($createColumn)->notNull();
        $this->dateTime($updateColumn);
        return $this;
    }
}
