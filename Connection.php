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

    /** @var collection of executed queries */
    protected $queries = [];

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
     * @param   array   Connection config
     * @return  object  A new connection instance.
     * @throws  Qubus\Exception\Exception When connection.
     */
    public static function instance($config = [])
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
    public function __construct($config = [])
    {
        if (isset($config['username'])) {
            unset($config['username']);
        }

        if (isset($config['password'])) {
            unset($config['password']);
        }

        $this->config = $config;
    }

    public function tablePrefix()
    {
        return $this->config['prefix'];
    }

    /**
     * Enables the profiling.
     *
     * @return  object  $this
     */
    public function enableProfiler()
    {
        $this->config['profiling'] = true;

        return $this;
    }

    /**
     * Enables the profiling, will clear out past queries on next execution.
     *
     * @return  object  $this
     */
    public function disableProfiling()
    {
        $this->config['profiling'] = false;

        return $this;
    }

    /**
     * Returnes the last executed query.
     *
     * @return  mixed  last executed query
     */
    public function lastQuery()
    {
        return $last = end($this->queries) ? $last['query'] : null;
    }

    /**
     * Returns an array of fired queries.
     *
     * @retun  array  fired queries
     */
    public function queries()
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
    public function profilerQueries()
    {
        return $this->queries;
    }

    /**
     * Returns the fired queries with profiling data.
     *
     * @return  array  profiler data about the queries
     */
    public function profilerCallbacks($start = null, $end = null)
    {
        $this->profilerCallbacks['start'] = $start;
        $this->profilerCallbacks['end'] = $end;
    }

    /**
     * Run transactional queries.
     *
     * @param   Closure  $callback  transaction callback
     * @return  object   $this
     * @throws
     */
    public function transaction(Closure $callback, $that = null)
    {
        if ($that === null) {
            $that = $this;
        }

        // check if we are in a transaction
        if ($this->inTransaction()) {
            return $callback($that);
        }

        try {
            // start the transaction
            $this->startTransaction();

            // execute the callback
            $callback($this);

            // all fine, commit the transaction
            $this->commitTransaction();
        }
        // catch any errors generated in the callback
        catch (PDOException $e) {
            // rolleback on error
            $this->rollbackTransaction();
            throw $e;
        }

        return $this;
    }

    /**
     * Transaction functions.
     */
    public function inTransaction()
    {
        throw new Exception('Transactions are not supported by this driver.');
    }
    
    public function startTransaction()
    {
        throw new Exception('Transactions are not supported by this driver.');
    }

    public function commitTransaction()
    {
        throw new Exception('Transactions are not supported by this driver.');
    }

    public function rollbackTransaction()
    {
        throw new Exception('Transactions are not supported by this driver.');
    }

    /**
     * Savepoints functions.
     */
    public function setSavepoint($savepoint = null)
    {
        throw new Exception('Savepoints are not supported by this driver.');
    }

    public function rollbackSavepoint($savepoint = null)
    {
        throw new Exception('Savepoints are not supported by this driver.');
    }

    public function releaseSavepoint($savepoint = null)
    {
        throw new Exception('Savepoints are not supported by this driver.');
    }

    /**
     * DB class call forwarding. Sets the current connection if setter is available.
     *
     * @param   string  $func  function name
     * @param   array   $args  function arguments
     * @return  forwarded result (with set connection)
     * @throws BadMethodCallException when method doesn't exist.
     */
    public function __call($func, $args)
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
     * @return  array  databases.
     */
    public function listDatabases()
    {
        throw new Exception('List database is not supported by this driver.');
    }

    /**
     * List database tables.
     *
     * @return  array  tables fields.
     */
    public function listTables()
    {
        throw new Exception('List tables is not supported by this driver.');
    }

    /**
     * List table fields.
     *
     * @return  array  databases.
     */
    public function listFields($table)
    {
        throw new Exception('List fields is not supported by this driver.');
    }

    abstract public function quoteIdentifier($value);
}
