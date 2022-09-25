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

declare(strict_types=1);

namespace Qubus\Dbal;

use Qubus\Exception\Exception;

use function is_array;

abstract class Base
{
    /** @var bool|string|null $asOjbect True for stCLass or string classname. */
    protected bool|string|null $asObject = null;

    /** @var bool $propertiesLate  true for assigning properties after object creation */
    protected bool $propertiesLate;

    /** @var array  $constructorArguments constructor arguments */
    protected array $constructorArguments = [];

    /** @var  array  $bindings  query bindings */
    protected array $bindings = [];

    /** @var  ?string  $type  query type */
    protected ?string $type = null;

    /** @var  ?Connection $connection  connection object */
    protected ?Connection $connection = null;

    /**
     * Bind a value to the query.
     *
     * @param mixed $key Binding key or associative array of bindings.
     * @param mixed|null $value Binding value.
     */
    public function bind(mixed $key, mixed $value = null): static
    {
        is_array(value: $key) || $key = [$key => $value];

        foreach ($key as $k => $v) {
            $this->bindings[$k] = $v;
        }

        return $this;
    }

    /**
     * Get the query value.
     *
     * @param Connection $connection Database connection object
     * @throws Exception
     */
    public function setConnection(Connection $connection): static
    {
        if (! $connection instanceof Connection) {
            throw new Exception(message: 'Supplied invalid connection object.');
        }

        $this->connection = $connection;

        return $this;
    }

    /**
     * Get the connection object.
     */
    public function getConnection(): ?Connection
    {
        return $this->connection;
    }

    /**
     * Get the query value.
     *
     * @return mixed Query contents.
     */
    abstract public function getContents(): mixed;

    /**
     * Returns the query's bindings.
     *
     * @return array Query bindings.
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * Returns the query type.
     *
     * @return string Query bindings.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Set the return type for SELECT statements
     *
     * @param bool|string|null $object Null for connection default, false for array, true for stdClass or string classname.
     * @param bool $propertiesLate Accessing properties late.
     * @param array $constructorArguments Constructor arguments.
     */
    public function asObject(bool|string|null $object = true, bool $propertiesLate = false, array $constructorArguments = []): static
    {
        $this->asObject = $object;
        $this->propertiesLate = $propertiesLate;
        $this->constructorArguments = $constructorArguments;

        return $this;
    }

    /**
     * When return type is classname you can assign properties late
     *
     * @param bool $propertiesLate false, true to assign properties late
     */
    public function setPropertiesLate(bool $propertiesLate = false): static
    {
        $this->propertiesLate = $propertiesLate;

        return $this;
    }

    /**
     * @return bool
     */
    public function getPropertiesLate(): bool
    {
        return $this->propertiesLate;
    }

    /**
     * ConstructorArguments set constructor arguments
     *
     * @param array $constructorArguments
     * @return Base
     */
    public function setConstructorArguments(array $constructorArguments = []): static
    {
        $this->constructorArguments = $constructorArguments;
        return $this;
    }

    /**
     * getConstructorArguments
     *
     * @return array
     */
    public function getConstructorArguments(): array
    {
        return $this->constructorArguments;
    }

    /**
     * Sets the return type to array
     */
    public function asAssoc(): static
    {
        $this->asObject = false;

        return $this;
    }

    /**
     * Returns whether to get as array or object
     *
     * @return  string|bool|null  null for array, true for stdClass or string for classname
     */
    public function getAsObject(): string|bool|null
    {
        return $this->asObject;
    }

    /**
     * Executes the query on a given connection.
     *
     * @param Connection|null $connection Qubus\Dbal\Connection
     * @return int|bool|array Query result.
     * @throws Exception
     */
    public function execute(Connection $connection = null): int|bool|array
    {
        $connection || $connection = $this->getConnection();

        if (! $connection) {
            throw new Exception(message: 'Cannot execute a query without a valid connection');
        }

        return $connection->execute(query: $this);
    }

    /**
     * Compiles the query on a given connection.
     *
     * @param Connection|null $connection Qubus\Dbal\Connection
     * @return string compiled query.
     * @throws Exception
     */
    public function compile(Connection $connection = null): string
    {
        $connection || $connection = $this->getConnection();

        if (! $connection) {
            throw new Exception(message: 'Cannot compile a query without a valid connection');
        }

        return $connection->compile($this, $this->getType());
    }
}
