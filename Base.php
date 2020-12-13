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
    /** @var mixed  $asOjbect  true for stCLass or string classname */
    protected $asObject;

    /** @var bool $propertiesLate  true for assigning properties after object creation */
    protected $propertiesLate;

    /** @var array  $constructorArguments constructor arguments */
    protected array $constructorArguments = [];

    /** @var  array  $bindings  query bindings */
    protected array $bindings = [];

    /** @var  string  $type  query type */
    protected $type;

    /** @var  object  $connection  connection object */
    protected Connection $connection;

    /**
     * Bind a value to the query.
     *
     * @param   mixed  $key    binding key or associative array of bindings
     * @param   mixed  $value  binding value
     */
    public function bind($key, $value = null)
    {
        is_array($key) || $key = [$key => $value];

        foreach ($key as $k => $v) {
            $this->bindings[$k] = $v;
        }

        return $this;
    }

    /**
     * Get the query value.
     *
     * @param object $connection Database connection object
     * @return object $this
     */
    public function setConnection(Connection $connection)
    {
        if (! $connection instanceof Connection) {
            throw new Exception('Supplied invalid connection object.');
        }

        $this->connection = $connection;

        return $this;
    }

    /**
     * Get the connection object.
     *
     * @return object Connection object.
     * @throws Exception When no connection object is set.
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
    abstract public function getContents();

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
     * @return array Query bindings.
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set the return type for SELECT statements
     *
     * @param mixed $object               For connection default, false for array, true for stdClass or string classname.
     * @param bool  $propertiesLate       Accessing properties late.
     * @param array $constructorArguments Constructor arguments.
     * @return object $this;
     */
    public function asObject($object = true, $propertiesLate = false, array $constructorArguments = [])
    {
        $this->asObject = $object;
        $this->propertiesLate = $propertiesLate;
        $this->constructorArguments = $constructorArguments;

        return $this;
    }

    /**
     * When return type is classname u can assign properties late
     *
     * @param bool  $propertieslate false, true to assign properties late
     * @return object  $this;
     */
    public function setPropertiesLate($propertieslate = false)
    {
        $this->propertiesLate = $propertieslate;

        return $this;
    }

    /**
     * @return bool
     */
    public function getPropertiesLate()
    {
        return $this->propertiesLate;
    }

    /**
     * ConstructorArguments set constructor arguments
     *
     * @param array $constructorArguments
     * @return object $this;
     */
    public function setConstructorArguments(array $constructorArguments = [])
    {
        $this->constructorArguments = $constructorArguments;
        return $this;
    }

    /**
     * getConstructorArguments
     *
     * @return array
     */
    public function getConstructorArguments()
    {
        return $this->constructorArguments;
    }

    /**
     * Sets the return type to array
     *
     * @return  object  $this;
     */
    public function asAssoc()
    {
        $this->asObject = false;

        return $this;
    }

    /**
     * Returns wether to get as array or object
     *
     * @return  mixed  null for array, true for stdClass or string for classname
     */
    public function getAsObject()
    {
        return $this->asObject;
    }

    /**
     * Executes the query on a given connection.
     *
     * @param   object  $connection  Qubus\Dbal\Connection
     * @return  mixed   Query result.
     */
    public function execute($connection = null)
    {
        $connection || $connection = $this->getConnection();

        if (! $connection) {
            throw new Exception('Cannot execute a query without a valid connection');
        }

        return $connection->execute($this);
    }

    /**
     * Compiles the query on a given connection.
     *
     * @param   object  $connection  Qubus\Dbal\Connection
     * @return  mixed   compiled query
     */
    public function compile($connection = null)
    {
        $connection || $connection = $this->getConnection();

        if (! $connection) {
            throw new Exception('Cannot compile a query without a valid connection');
        }

        return $connection->compile($this, $this->getType());
    }
}
