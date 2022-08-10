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

namespace Qubus\Dbal;

use Closure;
use PDO;
use PDOStatement;

use function call_user_func_array;
use function is_array;
use function Qubus\Support\Helpers\is_null__;

class ResultSet
{
    /** @var PDOStatement The PDOStatement associated with this result set. */
    protected PDOStatement $statement;

    /**
     * Constructor
     *
     * @param PDOStatement $statement The PDOStatement associated with this result set.
     */
    public function __construct(PDOStatement $statement)
    {
        $this->statement = $statement;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->statement->closeCursor();
    }

    /**
     * Count affected rows
     *
     * @return  int
     */
    public function count(): int
    {
        return $this->statement->rowCount();
    }

    /**
     * Fetch all results.
     *
     * @param callable|null $callable (optional) Callback function
     * @param int $fetchStyle (optional) PDO fetch style
     * @return array|false
     */
    public function all(?callable $callable = null, int $fetchStyle = 0): array|false
    {
        if (is_null__(var: $callable)) {
            return $this->statement->fetchAll(mode: $fetchStyle);
        }
        return $this->statement->fetchAll($fetchStyle | PDO::FETCH_FUNC, $callable);
    }

    /**
     * @param bool $uniq (optional)
     * @param callable|null $callable (optional)
     * @return array|false
     */
    public function allGroup(bool $uniq = false, ?callable $callable = null): array|false
    {
        $fetchStyle = PDO::FETCH_GROUP | ($uniq ? PDO::FETCH_UNIQUE : 0);
        if (is_null__($callable)) {
            return $this->statement->fetchAll(mode: $fetchStyle);
        }
        return $this->statement->fetchAll($fetchStyle | PDO::FETCH_FUNC, $callable);
    }

    /**
     * Fetch first result
     *
     * @param callable|null $callable (optional) Callback function
     * @return  mixed
     */
    public function first(?callable $callable = null): mixed
    {
        if ($callable !== null) {
            $result = $this->statement->fetch(PDO::FETCH_ASSOC);
            $this->statement->closeCursor();
            if (is_array(value: $result)) {
                $result = call_user_func_array(callback: $callable, args: $result);
            }
        } else {
            $result = $this->statement->fetch();
            $this->statement->closeCursor();
        }

        return $result;
    }

    /**
     * Fetch next result
     *
     * @return  mixed
     */
    public function next(): mixed
    {
        return $this->statement->fetch();
    }

    /**
     * Close current cursor
     *
     * @return  bool
     */
    public function flush(): bool
    {
        return $this->statement->closeCursor();
    }

    /**
     * Return a column
     *
     * @param int $col 0-indexed number of the column you wish to retrieve
     * @return  mixed
     */
    public function column(int $col = 0): mixed
    {
        return $this->statement->fetchColumn($col);
    }

    /**
     * Fetch each result as an associative array
     *
     * @return  $this
     */
    public function fetchAssoc(): static
    {
        $this->statement->setFetchMode(mode: PDO::FETCH_ASSOC);
        return $this;
    }

    /**
     * Fetch each result as an stdClass object
     *
     * @return  $this
     */
    public function fetchObject(): static
    {
        $this->statement->setFetchMode(mode: PDO::FETCH_OBJ);
        return $this;
    }

    /**
     * @return  $this
     */
    public function fetchNamed(): static
    {
        $this->statement->setFetchMode(mode: PDO::FETCH_NAMED);
        return $this;
    }

    /**
     * @return  $this
     */
    public function fetchNum(): static
    {
        $this->statement->setFetchMode(mode: PDO::FETCH_NUM);
        return $this;
    }

    /**
     * @return  $this
     */
    public function fetchBoth(): static
    {
        $this->statement->setFetchMode(mode: PDO::FETCH_BOTH);
        return $this;
    }

    /**
     * @return  $this
     */
    public function fetchKeyPair(): static
    {
        $this->statement->setFetchMode(mode: PDO::FETCH_KEY_PAIR);
        return $this;
    }

    /**
     * @param string $class
     * @param array $ctorargs (optional)
     * @return $this
     */
    public function fetchClass(string $class, array $ctorargs = []): static
    {
        $this->statement->setFetchMode(PDO::FETCH_CLASS, $class, $ctorargs);
        return $this;
    }

    /**
     * @return  $this
     */
    public function fetchCustom(Closure $func): static
    {
        $func($this->statement);
        return $this;
    }
}
