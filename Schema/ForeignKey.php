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

use function in_array;
use function strtoupper;

class ForeignKey
{
    protected string $refTable;

    /** @var string[] $refColumns */
    protected array $refColumns;

    /** @var array $actions */
    protected array $actions = [];

    /** @var string[] $columns */
    protected array $columns;

    /**
     * @param string[] $columns
     */
    public function __construct(array $columns)
    {
        $this->columns = $columns;
    }

    /**
     * @return $this
     */
    protected function addAction(string $on, string $action): self
    {
        $action = strtoupper(string: $action);

        if (! in_array(needle: $action, haystack: ['RESTRICT', 'CASCADE', 'NO ACTION', 'SET NULL'])) {
            return $this;
        }

        $this->actions[$on] = $action;
        return $this;
    }

    public function getReferencedTable(): string
    {
        return $this->refTable;
    }

    /**
     * @return string[]
     */
    public function getReferencedColumns(): array
    {
        return $this->refColumns;
    }

    /**
     * @return string[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return array
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * @param string[] $columns
     * @return $this
     */
    public function references(string $table, string ...$columns): self
    {
        $this->refTable = $table;
        $this->refColumns = $columns;
        return $this;
    }

    /**
     * @return $this
     */
    public function onDelete(string $action): self
    {
        return $this->addAction(on:'ON DELETE', action: $action);
    }

    /**
     * @return $this
     */
    public function onUpdate(string $action): self
    {
        return $this->addAction(on:'ON UPDATE', action: $action);
    }
}
