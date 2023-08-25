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

namespace Qubus\Dbal\Schema;

class CreateColumn extends BaseColumn
{
    /** @var ?CreateTable $table */
    protected ?CreateTable $table = null;

    public function __construct(CreateTable $table, string $name, string $type)
    {
        $this->table = $table;
        parent::__construct(name: $name, type: $type);
    }

    public function getTable(): CreateTable
    {
        return $this->table;
    }

    /**
     * @return $this
     */
    public function autoincrement(?string $name = null): self
    {
        $this->table->autoincrement(column: $this, name: $name);
        return $this;
    }

    /**
     * @return $this
     */
    public function primary(?string $name = null): self
    {
        $this->table->primary(columns: $this->name, name: $name);
        return $this;
    }

    /**
     * @return $this
     */
    public function unique(?string $name = null): self
    {
        $this->table->unique(columns: $this->name, name: $name);
        return $this;
    }

    /**
     * @return $this
     */
    public function index(?string $name = null): self
    {
        $this->table->index(columns: $this->name, name: $name);
        return $this;
    }
}
