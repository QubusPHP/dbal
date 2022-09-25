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
use function strtolower;

class BaseColumn
{
    protected ?string $name = null;

    protected ?string $type = null;

    /** @var array $properties */
    protected array $properties = [];

    public function __construct(string $name, ?string $type = null)
    {
        $this->name = $name;
        $this->type = $type;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @return array
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * @return $this
     */
    public function setType(string $type): self
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function set(string $name, mixed $value): self
    {
        $this->properties[$name] = $value;
        return $this;
    }

    public function has(string $name): bool
    {
        return isset($this->properties[$name]);
    }

    /**
     * @param mixed|null $default
     * @return mixed|null
     */
    public function get(string $name, mixed $default = null): mixed
    {
        return $this->properties[$name] ?? $default;
    }

    /**
     * @return $this
     */
    public function size(string $value): self
    {
        $value = strtolower(string: $value);

        if (! in_array(needle: $value, haystack: ['tiny', 'small', 'normal', 'medium', 'big'])) {
            return $this;
        }

        return $this->set(name: 'size', value: $value);
    }

    /**
     * @return $this
     */
    public function notNull(): self
    {
        return $this->set(name: 'nullable', value: false);
    }

    /**
     * @return $this
     */
    public function description(string $comment): self
    {
        return $this->set(name: 'description', value: $comment);
    }

    /**
     * @param $value
     * @return $this
     */
    public function defaultValue($value): self
    {
        return $this->set(name: 'default', value: $value);
    }

    /**
     * @return $this
     */
    public function unsigned(bool $value = true): self
    {
        return $this->set(name: 'unsigned', value: $value);
    }

    /**
     * @param mixed $value
     * @return $this
     */
    public function length(mixed $value): self
    {
        return $this->set(name: 'length', value: $value);
    }
}
