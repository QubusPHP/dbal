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

namespace Qubus\Dbal\Connection;

use Closure;
use PDO;
use PDOException;
use PDOStatement;
use Qubus\Dbal\Base;
use Qubus\Dbal\Connection;
use Qubus\Dbal\DB;
use Qubus\Dbal\DsnGenerator;
use Qubus\Dbal\Expression;
use Qubus\Dbal\Fnc;
use Qubus\Dbal\Query;
use Qubus\Dbal\ResultSet;
use Qubus\Dbal\Schema;
use Qubus\Exception\Exception;

use function array_map;
use function array_merge;
use function array_shift;
use function class_exists;
use function count;
use function defined;
use function explode;
use function get_class;
use function implode;
use function is_array;
use function is_bool;
use function is_float;
use function is_int;
use function is_numeric;
use function is_object;
use function is_string;
use function microtime;
use function preg_replace_callback;
use function sprintf;
use function strpos;
use function strtolower;
use function trigger_error;
use function ucfirst;

use const E_USER_ERROR;
use const PHP_OS;

class DbalPdo extends Connection
{
    /** @var  string  $tableQuote  table quote */
    protected static $tableQuote = '`';

    /** @var  string  $driver */
    protected $driver;

    /** @var  object  $connection  Dbal Compiler object */
    protected $compiler;

    /** @var  string  $insertIdField  field used for lastInsertId */
    public $insertIdField;

    /** @var  string  $charset  connection charset */
    public $charset;

    /** @var  int  $savepoint  auto savepoint level */
    protected int $savepoint = 0;

    /** @var array $config */
    protected array $config;

    /** @var PDO pdoInstance */
    private ?PDO $pdoInstance = null;

    private ?string $pdoDriver = null;

    /** @var array $commands */
    protected array $commands = [];

    protected ?Schema\Compiler $schemaCompiler = null;

    /** @var Schema $chema Schema instance */
    protected ?Schema $schema = null;

    /** @var array $compilerOptions Compiler options */
    protected array $compilerOptions = [];

    /** @var array $schemaCompilerOptions Schema compiler options */
    protected array $schemaCompilerOptions = [];

    protected ?string $dsn = null;

    /**
     * Connects to a database with the supplied config.
     */
    public function __construct($config = [])
    {
        // set the config defaults
        $this->config = array_merge([
            'driver'        => 'mysql',
            'host'          => 'localhost',
            'dbname'        => null,
            'username'      => null,
            'password'      => null,
            'attrs'         => [],
            'insertIdField' => null,
            'charset'       => 'UTF8',
            'persistent'    => false,
        ], $config);

        // store the driver
        $this->driver = strtolower($this->config['driver']);

        // get connected
        $this->loadDatabase();

        parent::__construct($this->config);
    }

    private function buildDsn(): ?string
    {
        if (isset($this->config['dsn'])) {
            return $this->config['dsn'];
        }
        $this->dsn = null;
        $generator = new DsnGenerator();
        switch ($this->pdoDriver) :
            case 'sqlsrv':
                $this->dsn = $generator->getSqlsrvDNS($this);
        break;

        case 'dblib':
                $this->dsn = $generator->getDblibDNS($this);
        break;

        case 'sqlite':
                $this->dsn = $generator->getSqliteDNS($this);
        break;

        case 'pgsql':
                $this->dsn = $generator->getPgsqlDNS($this);
        break;

        case 'oci':
                $this->dsn = $generator->getOracleDNS($this);
        break;

        case 'ibm':
                $this->dsn = $generator->getIbmDNS($this);
        break;

        default:
                $this->dsn = $generator->getMysqlDNS($this);
        break;
        endswitch;
        return $this->dsn;
    }

    /**
     * @return mixed|PDO
     */
    private function loadDatabase()
    {
        if (! $this->pdoInstance) {
            try {
                $dsn = $this->buildDsn();
                $options = $this->resolveOptions();
                if (
                    ! $this->pdoInstance = new PDO(
                        $dsn,
                        $this->config['username'],
                        $this->config['password'],
                        $options['attr']
                    )
                ) {
                    throw new PDOException('Connection to the database could not be established');
                }
                if (count($options['cmd']) > 0) {
                    foreach ($options['cmd'] as $cmd) {
                        $this->pdoInstance->exec($cmd);
                    }
                }
            } catch (PDOException $e) {
                trigger_error($e->getMessage(), E_USER_ERROR);
            }
        }
        return $this->pdoInstance;
    }

    /**
     * @return mixed
     */
    private function resolveOptions()
    {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        ];
        $command = [];
        $params = $this->config;
        if ($this->getName($params['driver']) === 'mysql') {
            if (defined('PDO::MYSQL_ATTR_INIT_COMMAND')) {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] = 'SET NAMES ' . $params['charset'];
            }

            $command[] = 'SET SQL_MODE=ANSI_QUOTES';
            $options[PDO::MYSQL_ATTR_USE_BUFFERED_QUERY] = false;
            $options[PDO::MYSQL_ATTR_COMPRESS] = true;
        }

        if ($params['fetchmode'] !== 'object') {
            $options[PDO::ATTR_DEFAULT_FETCH_MODE] = PDO::FETCH_ASSOC;
        }

        if (! $params['persistent']) {
            $options[PDO::ATTR_PERSISTENT] = false;
        }

        if (! $params['prepare']) {
            $options[PDO::ATTR_EMULATE_PREPARES] = false;
        }

        if (! isset($options[PDO::MYSQL_ATTR_INIT_COMMAND]) && ($this->getName($params['driver']) !== 'oci')) {
            $command[] = 'SET NAMES ' . $params['charset'];
        }

        if ($this->getName($params['driver']) === 'sqlsrv') {
            $command[] = 'SET QUOTED_IDENTIFIER ON';
        }

        return ['attr' => $options, 'cmd' => $command];
    }

    /**
     * @return mixed
     */
    public function serverVersion()
    {
        if (! $this->pdoInstance instanceof PDO) {
            return false;
        }
        return $this->pdoInstance->getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    public function getName(string $driver)
    {
        if (! $driver) {
            $driver = 'mysql';
        }
        $driver = strtolower($driver);
        switch ($driver) {
            case strpos($driver, 'mssql'):
            case strpos($driver, 'sqlserver'):
            case strpos($driver, 'sqlsrv'):
                $driver = strpos(PHP_OS, 'WIN') !== false ? 'sqlsrv' : 'dblib';
                break;
            case strpos($driver, 'sybase'):
                $driver = 'dblib';
                break;
            case strpos($driver, 'pgsql'):
                $driver = 'pgsql';
                break;
            case strpos($driver, 'sqlite'):
                $driver = 'sqlite';
                break;
            case strpos($driver, 'ibm'):
            case strpos($driver, 'db2'):
            case strpos($driver, 'odbc'):
                $driver = 'ibm';
                break;
            case strpos($driver, 'oracle'):
                $driver = 'oci';
                break;
            default:
                $driver = 'mysql';
                break;
        }
        return $driver;
    }

    public function getPdo(): PDO
    {
        try {
            if (! $this->pdoInstance instanceof PDO) {
                throw new Exception('No Connection has been established with the database.');
            }

            foreach ($this->commands as $command) {
                $this->command($command['sql'], $command['params']);
            }
        } catch (Exception $a) {
            trigger_error($a->getMessage(), E_USER_ERROR);
        }
        return $this->pdoInstance;
    }

    /**
     * Returns an instance of the schema compiler associated with this connection
     *
     * @throws Exception
     */
    public function schemaCompiler(): Schema\Compiler
    {
        if ($this->schemaCompiler === null) {
            switch ($this->getDriver()) {
                case 'mysql':
                    $this->schemaCompiler = new Schema\Compiler\MySQL($this);
                    break;
                case 'pgsql':
                    $this->schemaCompiler = new Schema\Compiler\PostgreSQL($this);
                    break;
                case 'dblib':
                case 'mssql':
                case 'sqlsrv':
                case 'sybase':
                    $this->schemaCompiler = new Schema\Compiler\SQLServer($this);
                    break;
                case 'sqlite':
                    $this->schemaCompiler = new Schema\Compiler\SQLite($this);
                    break;
                case 'oci':
                case 'oracle':
                    $this->schemaCompiler = new Schema\Compiler\Oracle($this);
                    break;
                default:
                    throw new Exception('Schema not supported yet');
            }

            $this->schemaCompiler->setOptions($this->schemaCompilerOptions);
        }

        return $this->schemaCompiler;
    }

    /**
     * Close the current connection by destroying the associated PDO object
     */
    public function disconnect()
    {
        $this->pdoInstance = null;
    }

    /**
     * gets configurations
     *
     * @return array
     */
    public function getConfigurations(): array
    {
        return $this->config;
    }

    /**
     * Add an init command.
     *
     * @param string $query SQL command.
     * @param array $params (optional) Params
     * @return Connection
     */
    public function initCommand(string $query, array $params = []): self
    {
        $this->commands[] = [
            'sql'    => $query,
            'params' => $params,
        ];

        return $this;
    }

    /**
     * Returns the DSN associated with this connection
     *
     * @return string
     */
    public function getDsn()
    {
        return $this->dsn;
    }

    /**
     * Returns the driver's name.
     *
     * @return string
     */
    public function getDriver()
    {
        if ($this->pdoDriver === null) {
            $this->pdoDriver = $this->getPdo()->getAttribute(PDO::ATTR_DRIVER_NAME);
        }

        return $this->pdoDriver;
    }

    /**
     * Returns the schema associated with this connection
     */
    public function getSchema(): Schema
    {
        if ($this->schema === null) {
            $this->schema = new Schema($this);
        }

        return $this->schema;
    }

    /**
     * Execute a query
     *
     * @param string $sql SQL Query
     * @param array $params (optional) Query params
     * @return ResultSet
     */
    public function query(string $sql, array $params = [])
    {
        $prepared = $this->prepare($sql, $params);
        $this->pdoExecute($prepared);
        return new ResultSet($prepared['statement']);
    }

    /**
     * Execute a non-query SQL command
     *
     * @param string $sql SQL Command
     * @param array $params (optional) Command params
     * @return mixed Command result
     */
    public function command(string $sql, array $params = [])
    {
        return $this->pdoExecute($this->prepare($sql, $params));
    }

    /**
     * Execute a query and return the number of affected rows
     *
     * @param string $sql SQL Query
     * @param array $params (optional) Query params
     * @return  int
     */
    public function count(string $sql, array $params = [])
    {
        $prepared = $this->prepare($sql, $params);
        $this->pdoExecute($prepared);
        $result = $prepared['statement']->rowCount();
        $prepared['statement']->closeCursor();
        return $result;
    }

    /**
     * Execute a query and fetch the first column
     *
     * @param   string $sql SQL Query
     * @param   array $params (optional) Query params
     * @return  mixed
     */
    public function column(string $sql, array $params = [])
    {
        $prepared = $this->prepare($sql, $params);
        $this->pdoExecute($prepared);
        $result = $prepared['statement']->fetchColumn();
        $prepared['statement']->closeCursor();
        return $result;
    }

    /**
     * Replace placeholders with parameters.
     *
     * @param string $query SQL query
     * @param array $params Query parameters
     */
    protected function replaceParams(string $query, array $params): string
    {
        return preg_replace_callback('/\?/', function () use (&$params) {
            $param = array_shift($params);
            $param = is_object($param) ? get_class($param) : $param;

            if (is_int($param) || is_float($param)) {
                return $param;
            } elseif ($param === null) {
                return 'null';
            } elseif (is_bool($param)) {
                return $param ? 'true' : 'false';
            } else {
                return $this->getPdo()->quote($param);
            }
        }, $query);
    }

    /**
     * Prepares a query.
     *
     * @param   string $query SQL query
     * @param   array $params Query parameters
     * @return  array
     */
    protected function prepare(string $query, array $params): array
    {
        try {
            $statement = $this->getPdo()->prepare($query);
        } catch (PDOException $e) {
            throw new PDOException(
                $e->getMessage() . ' [ ' . $this->replaceParams($query, $params) . ' ] ',
                (int) $e->getCode(),
                $e->getPrevious()
            );
        }

        return ['query' => $query, 'params' => $params, 'statement' => $statement];
    }

    /**
     * @param array $values
     */
    protected function bindValues(PDOStatement $statement, array $values)
    {
        foreach ($values as $key => $value) {
            $param = PDO::PARAM_STR;

            if (null === $value) {
                $param = PDO::PARAM_NULL;
            } elseif (is_int($value)) {
                $param = PDO::PARAM_INT;
            } elseif (is_bool($value)) {
                $param = PDO::PARAM_BOOL;
            }

            $statement->bindValue($key + 1, $value, $param);
        }
    }

    /**
     * Quotes an identifier
     *
     * @param   mixed   $value  value to quote
     * @return  string  quoted identifier
     */
    public function quoteIdentifier($value)
    {
        if ($value === '*') {
            return $value;
        }

        if (is_object($value)) {
            if ($value instanceof Base) {
                // Create a sub-query
                return '(' . $value->compile($this) . ')';
            } elseif ($value instanceof Expression) {
                // Use a raw expression
                return $value->handle($this->compiler);
            } elseif ($value instanceof Fnc) {
                return $this->compiler->compilePartFnc($value);
            } else {
                // Convert the object to a string
                return $this->quoteIdentifier((string) $value);
            }
        }

        if (is_array($value)) {
            // Separate the column and alias
            [$_value, $alias] = $value;
            return $this->quoteIdentifier($_value) . ' AS ' . $this->quoteIdentifier($alias);
        }

        if (strpos($value, '"') !== false) {
            // Quote the column in FUNC("ident") identifiers
            return preg_replace_callback('/"(.+?)"/', function ($matches) {
                return $this->quoteIdentifier($matches[1]);
            }, $value);
        }

        if (strpos($value, '.') !== false) {
            // Split the identifier into the individual parts
            $parts = explode('.', $value);

            // Quote each of the parts
            return implode('.', array_map([$this, __FUNCTION__], $parts));
        }

        return static::$tableQuote . $value . static::$tableQuote;
    }

    /**
     * Quote a value for an SQL query.
     *
     * Objects passed to this function will be converted to strings.
     * Expression objects will use the value of the expression.
     * Query objects will be compiled and converted to a sub-query.
     * Fnc objects will be send of for compiling.
     * All other objects will be converted using the `__toString` method.
     *
     * @param array|string   any value to quote
     * @return string
     */
    public function quote($value)
    {
        try {
            if (! $this->pdoInstance instanceof PDO) {
                throw new Exception('No PDOInstance has been made with the connection.');
            }

            if ($value === null) {
                return 'NULL';
            }

            if (is_bool($value)) {
                return $value ? 1 : 0;
            }

            if (is_object($value)) {
                if ($value instanceof Base) {
                    // create a sub-query
                    return '(' . $value->compile($this) . ')';
                }

                if ($value instanceof Fnc) {
                    // compile the function
                    return $this->compiler->compilePartFnc($value);
                }

                if ($value instanceof Expression) {
                    // get the output from the expression
                    return $value->handle($this->compiler);
                } else {
                    // Convert the object to a string
                    return $this->quote((string) $value);
                }
            }

            if (is_array($value)) {
                return '(' . implode(', ', array_map([$this, 'quote'], $value)) . ')';
            }

            if (is_int($value)) {
                return (int) $value;
            }

            if (is_float($value)) {
                // Convert to non-locale aware float to prevent possible commas
                return sprintf('%F', $value);
            }

            if (is_numeric($value) && ! is_string($value)) {
                return (string) $value;
            }
        } catch (Exception $a) {
            trigger_error($a->getMessage(), E_USER_ERROR);
        }

        return $this->pdoInstance->quote($value);
    }

    /**
     * Sets the connection encoding.
     *
     * @param  string  $charset  encoding
     */
    protected function setCharset($charset)
    {
        if (! empty($charset)) {
            $this->pdoInstance->exec("SET NAMES {$this->quote($charset)}");
        }
    }

    /**
     * Get the query compiler.
     *
     * @return  object  Dbal compiler object
     */
    protected function getCompiler()
    {
        if (! $this->compiler) {
            $class = 'Qubus\\Dbal\\Sql\\Compiler\\' . ucfirst($this->driver);

            if (! class_exists($class)) {
                throw new Exception('Cannot locate compiler for dialect: ' . $class);
            }

            $this->compiler = new $class($this);
        }

        return $this->compiler;
    }

    /**
     * Executes a prepared query and returns true on success or false on failure.
     *
     * @param   array $prepared Prepared query
     * @return  boolean
     */
    protected function pdoExecute(array $prepared)
    {
        try {
            if ($prepared['params']) {
                $this->bindValues($prepared['statement'], $prepared['params']);
            }
            $result = $prepared['statement']->execute();
        } catch (PDOException $e) {
            throw new PDOException($e->getMessage() . ' [ ' . $this->replaceParams(
                $prepared['query'],
                $prepared['params']
            ) . ' ] ', (int) $e->getCode(), $e->getPrevious());
        }

        return $result;
    }

    /**
     * Executes a query on a connection
     *
     * @param   object  $query     query object
     * @param   string  $type      query type
     * @param   array   $bindings  query bindings
     * @return  mixed   query results
     */
    public function execute($query, $type = null, array $bindings = [])
    {
        if (! $query instanceof Base) {
            $query = new Query($query, $type);
        }

        $type = $type ?: $query->getType();
        $sql = $this->compile($query, $type, $bindings);

        $profilerData = [
            'query'  => $sql,
            'start'  => microtime(true),
            'type'   => $type,
            'driver' => static::class . ':' . $this->driver,
        ];

        // fire start callback for profiling
        $this->profilerCallbacks['start'] instanceof Closure && $this->profilerCallbacks['start']($profilerData);

        try {
            $result = $this->pdoInstance->prepare($sql);
            $result->execute($bindings);
        } catch (PDOException $e) {
            $code = is_int($e->getCode()) ? $e->getCode() : 0;
            throw new Exception($e->getMessage() . ' from QUERY: ' . $sql, $code);
        }

        if ($type === DB::SELECT) {
            $asObject = $query->getAsObject();
            $asObject === null && $asObject = $this->config['asObject'];

            if (! $asObject) {
                $result = $result->fetchAll(PDO::FETCH_ASSOC);
            } elseif (is_string($asObject)) {
                $propertiesLate = $query->getPropertiesLate();
                $propertiesLate === null && $propertiesLate = $this->config['propertiesLate'];

                $constructorArguments = $query->getConstructorArguments();

                $fetchStyle = PDO::FETCH_CLASS;

                if ($propertiesLate) {
                    $fetchStyle = PDO::FETCH_CLASS | PDO::FETCH_PROPS_LATE;
                }

                $result = $result->fetchAll($fetchStyle, $asObject, $constructorArguments);
            } else {
                $result = $result->fetchAll(PDO::FETCH_CLASS, 'stdClass');
            }
        } elseif ($type === DB::INSERT) {
            $result = [
                $this->pdoInstance->lastInsertId($query->insertIdField() ?: $this->insertIdField),
                $result->rowCount(),
            ];
        } else {
            $result = $result->errorCode() === '00000' ? $result->rowCount() : -1;
        }

        $profilerData['end'] = microtime(true);
        $profilerData['duration'] = $profilerData['end'] - $profilerData['start'];

        // clear out any previous queries when profiling is turned off.
        // This will save memory, better for performance.
        if ($this->config['profiling'] === false) {
            $this->queries = [];
        }

        // always save the last query, for lastQuery support
        $this->queries[] = $profilerData;

        // fire eny given profiler callbacks
        $this->profilerCallbacks['end'] instanceof Closure && $this->profilerCallbacks['end']($profilerData);

        return $result;
    }

    /**
     * Compile the query.
     *
     * @param   object  $query     query object
     * @param   string  $type      query type
     * @param   array   $bindings  query bindings
     */
    public function compile($query, ?string $type = null, array $bindings = [])
    {
        if (! $query instanceof Base) {
            $query = new Query($query, $type);
        }

        // Re-retrieve the query type
        $type = $query->getType();

        return $this->getCompiler()->compile($query, $type, $bindings);
    }

    /**
     * Sets transaction savepoint.
     *
     * @param   string  $savepoint  savepoint name
     * @return  object  $this
     */
    public function setSavepoint($savepoint = null)
    {
        $savepoint || $savepoint = 'DBAL_SP_LEVEL_' . ++$this->savepoint;
        $this->pdoInstance->query('SAVEPOINT ' . $savepoint);

        return $this;
    }

    /**
     * Roll back to a transaction savepoint.
     *
     * @param   string  $savepoint  savepoint name
     * @return  object  $this
     */
    public function rollbackSavepoint($savepoint = null)
    {
        if (! $savepoint) {
            $savepoint = 'DBAL_SP_LEVEL_' . $this->savepoint;
            $this->savepoint--;
        }

        $this->pdoInstance->query('ROLLBACK TO SAVEPOINT ' . $savepoint);

        return $this;
    }

    /**
     * Release a transaction savepoint.
     *
     * @param   string  $savepoint  savepoint name
     * @return  object  $this
     */
    public function releaseSavepoint($savepoint = null)
    {
        if (! $savepoint) {
            $savepoint = 'DBAL_SP_LEVEL_' . $this->savepoint;
            $this->savepoint--;
        }

        $this->pdoInstance->query('RELEASE SAVEPOINT ' . $savepoint);

        return $this;
    }

    /**
     * Object destruct closes the database connection.
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}
