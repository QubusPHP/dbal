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

use BadMethodCallException;
use Closure;
use PDOException;
use Qubus\Exception\Exception;

use function array_map;
use function array_merge;
use function call_user_func_array;
use function class_exists;
use function end;
use function is_callable;
use function is_object;
use function method_exists;
use function Qubus\Support\Helpers\is_null__;
use function strtolower;
use function ucfirst;

abstract class Connection
{
    public const DEFAULT_PARAMETERS = [
        'type'           => 'pdo',
        'driver'         => 'mysql',
        'profiling'      => false,
        'asObject'       => true,
        'propertiesLate' => false,
        'charset'        => 'utf8',
        'host'           => 'localhost',
        'dbname'         => null,
        'port'           => 3306,
        'username'       => null,
        'password'       => null,
        'persistent'     => false,
        'fetchmode'      => 'object',
        'prepare'        => false,
    ];

    /** @var array collection of executed queries */
    protected array $queries = [];

    /** @var array  profiler callbacks */
    protected array $profilerCallbacks = [
        'start' => null,
        'end'   => null,
    ];

    /** @var  array  $config  connection config */
    protected array $config;

    /**
     * Returns a connection instance based on the config.
     *
     * @param array $config Connection config
     * @throws Exception
     */
    public static function instance(array $config = []): Connection
    {
        $config = array_merge(self::DEFAULT_PARAMETERS, $config);

        $class = ucfirst(strtolower($config['type']));
        $config['driver'] && $class .= '\\' . ucfirst(strtolower($config['driver']));

        if (! class_exists($class = __NAMESPACE__ . '\\Connection\\' . $class)) {
            throw new Exception('Cannot load database connection: ' . $class);
        }

        return new $class($config);
    }

    /**
     * Constructor, sets the main config array.
     */
    public function __construct(array $config = [])
    {
        if (isset($config['username'])) {
            unset($config['username']);
        }

        if (isset($config['password'])) {
            unset($config['password']);
        }

        $this->config = $config;
    }

    public function tablePrefix(): string
    {
        return $this->config['prefix'];
    }

    /**
     * Enables the profiling.
     */
    public function enableProfiler(): static
    {
        $this->config['profiling'] = true;

        return $this;
    }

    /**
     * Enables the profiling, will clear out past queries on next execution.
     */
    public function disableProfiling(): static
    {
        $this->config['profiling'] = false;

        return $this;
    }

    /**
     * Returns the last executed query.
     *
     * @return  mixed  last executed query
     */
    public function lastQuery(): mixed
    {
        return $last = end($this->queries) ? $last['query'] : null;
    }

    /**
     * Returns an array of fired queries.
     *
     * @return  array  fired queries
     */
    public function queries(): array
    {
        return array_map(function ($i) {
            return $i['query'];
        }, $this->queries);
    }

    /**
     * Returns the fired queries with profiling data.
     *
     * @return  array  profiler data about the queries
     */
    public function profilerQueries(): array
    {
        return $this->queries;
    }

    /**
     * Returns the fired queries with profiling data.
     */
    public function profilerCallbacks(mixed $start = null, mixed $end = null): void
    {
        $this->profilerCallbacks['start'] = $start;
        $this->profilerCallbacks['end'] = $end;
    }

    /**
     * Run transactional queries.
     *
     * @param Closure $callback transaction callback
     * @throws Exception
     */
    public function transaction(Closure $callback, mixed $that = null, mixed $default = null)
    {
        if (is_null__($that)) {
            $that = $this;
        }

        // check if we are in a transaction
        if ($this->inTransaction()) {
            return $callback($that);
        }

        $result = $default;

        try {
            // start the transaction
            $this->startTransaction();

            // execute the callback
            $result = $callback($this);

            // all fine, commit the transaction
            $this->commitTransaction();
        } catch (PDOException $e) { // catch any errors generated in the callback
            // rollback on error
            $this->rollbackTransaction();
            throw new DbalException(message: $e->getMessage(), code: (int) $e->getCode());
        }

        return $result;
    }

    /**
     * Transaction functions.
     *
     * @throws Exception
     */
    public function inTransaction()
    {
        throw new Exception('Transactions are not supported by this driver.');
    }

    /**
     * @throws Exception
     */
    public function startTransaction()
    {
        throw new Exception('Transactions are not supported by this driver.');
    }

    /**
     * @throws Exception
     */
    public function commitTransaction()
    {
        throw new Exception('Transactions are not supported by this driver.');
    }

    /**
     * @throws Exception
     */
    public function rollbackTransaction()
    {
        throw new Exception('Transactions are not supported by this driver.');
    }

    /**
     * Savepoints functions.
     *
     * @throws Exception
     */
    public function setSavepoint(mixed $savepoint = null)
    {
        throw new Exception('Savepoints are not supported by this driver.');
    }

    /**
     * @throws Exception
     */
    public function rollbackSavepoint(mixed $savepoint = null)
    {
        throw new Exception('Savepoints are not supported by this driver.');
    }

    /**
     * @throws Exception
     */
    public function releaseSavepoint(mixed $savepoint = null)
    {
        throw new Exception('Savepoints are not supported by this driver.');
    }

    /**
     * DB class call forwarding. Sets the current connection if setter is available.
     *
     * @param   string  $func  function name
     * @param   array   $args  function arguments
     * @throws BadMethodCallException When method doesn't exist.
     */
    public function __call(string $func, array $args)
    {
        $call = '\\Qubus\\Dbal\\DB::' . $func;

        if (is_callable($call)) {
            $return = call_user_func_array($call, $args);

            if (is_object($return) && method_exists($return, 'setConnection')) {
                $return->setConnection($this);
            }

            return $return;
        }

        throw new BadMethodCallException($func . ' is not a method of ' . static::class);
    }

    /**
     * List databases.
     *
     * @throws Exception
     */
    public function listDatabases()
    {
        throw new Exception('List database is not supported by this driver.');
    }

    /**
     * List database tables.
     *
     * @throws Exception
     */
    public function listTables()
    {
        throw new Exception('List tables is not supported by this driver.');
    }

    /**
     * List table fields.
     *
     * @throws Exception
     */
    public function listFields(mixed $table)
    {
        throw new Exception('List fields is not supported by this driver.');
    }

    abstract public function quoteIdentifier(mixed $value): mixed;
}
