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

class AlterColumn extends BaseColumn
{
    /** @var string|AlterTable|null $table */
    protected string|AlterTable|null $table = null;

    public function __construct(AlterTable $table, string $name, ?string $type = null)
    {
        $this->table = $table;
        parent::__construct($name, $type);
    }

    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * @inheritDoc
     */
    public function defaultValue($value): BaseColumn
    {
        if ($this->get(name: 'handleDefault', default: true)) {
            return parent::defaultValue(value: $value);
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function autoincrement(): self
    {
        return $this->set(name: 'autoincrement', value: true);
    }
}
