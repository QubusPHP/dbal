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
use Qubus\Dbal\DbalException;
use Qubus\Dbal\DsnGenerator;
use Qubus\Dbal\Expression;
use Qubus\Dbal\Fnc;
use Qubus\Dbal\Query;
use Qubus\Dbal\ResultSet;
use Qubus\Dbal\Schema;
use Qubus\Dbal\Sql\Compiler;
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
use function Qubus\Support\Helpers\is_null__;
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
    protected static string $tableQuote = '`';

    /** @var  ?string  $driver */
    protected ?string $driver = null;

    /** @var  ?Compiler $compiler  Dbal Compiler object */
    protected ?Compiler $compiler = null;

    /** @var  ?string  $insertIdField  field used for lastInsertId */
    public ?string $insertIdField = null;

    /** @var  ?string  $charset  connection charset */
    public ?string $charset = null;

    /** @var  int  $savepoint  auto savepoint level */
    protected int $savepoint = 0;

    /** @var array $config */
    protected array $config = [];

    /** @var ?PDO pdoInstance */
    protected ?PDO $pdoInstance = null;

    private ?string $pdoDriver = null;

    /** @var array $commands */
    protected array $commands = [];

    protected ?Schema\Compiler $schemaCompiler = null;

    /** @var ?Schema $schema Schema instance */
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
            'charset'       => 'utf8mb4',
            'collation'     => 'utf8mb4_unicode_ci',
            'persistent'    => false,
        ], $config);

        // store the driver
        $this->driver = strtolower(string: $this->config['driver']);

        // get connected
        $this->loadDatabase();

        parent::__construct(config: $this->config);
    }

    private function buildDsn(): ?string
    {
        if (isset($this->config['dsn'])) {
            return $this->config['dsn'];
        }

        $this->dsn = null;

        $generator = new DsnGenerator();

        $this->dsn = match ($this->pdoDriver) {
            'sqlsrv' => $generator->getSqlsrvDNS($this),
            'dblib' => $generator->getDblibDNS($this),
            'sqlite' => $generator->getSqliteDNS($this),
            'pgsql' => $generator->getPgsqlDNS($this),
            'oci' => $generator->getOracleDNS($this),
            'ibm' => $generator->getIbmDNS($this),
            default => $generator->getMysqlDNS($this),
        };

        return $this->dsn;
    }

    /**
     * @return PDO|null
     */
    private function loadDatabase(): ?PDO
    {
        if (! $this->pdoInstance) {
            try {
                $dsn = $this->buildDsn();
                $options = $this->resolveOptions();
                if (
                    ! $this->pdoInstance = new PDO(
                        dsn: $dsn,
                        username: $this->config['username'],
                        password: $this->config['password'],
                        options: $options['attr']
                    )
                ) {
                    throw new DbalException(message: 'Connection to the database could not be established');
                }
                if (count($options['cmd']) > 0) {
                    foreach ($options['cmd'] as $cmd) {
                        $this->pdoInstance->exec(statement: $cmd);
                    }
                }
            } catch (PDOException $e) {
                trigger_error(message: $e->getMessage(), error_level: E_USER_ERROR);
            }
        }
        return $this->pdoInstance;
    }

    /**
     * @return mixed
     */
    private function resolveOptions(): mixed
    {
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
        ];
        $command = [];
        $params = $this->config;
        if ($this->getName(driver: $params['driver']) === 'mysql') {
            if (defined(constant_name: 'PDO::MYSQL_ATTR_INIT_COMMAND')) {
                $options[PDO::MYSQL_ATTR_INIT_COMMAND] =
                'SET NAMES ' . $params['charset'] . ' COLLATE ' . $params['collation'];
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

        if (! isset($options[PDO::MYSQL_ATTR_INIT_COMMAND]) && ($this->getName(driver: $params['driver']) !== 'oci')) {
            $command[] = 'SET NAMES ' . $params['charset'] . ' COLLATE ' . $params['collation'];
        }

        if ($this->getName(driver: $params['driver']) === 'sqlsrv') {
            $command[] = 'SET QUOTED_IDENTIFIER ON';
        }

        return ['attr' => $options, 'cmd' => $command];
    }

    /**
     * @return mixed
     */
    public function serverVersion(): mixed
    {
        if (! $this->pdoInstance instanceof PDO) {
            return false;
        }
        return $this->pdoInstance->getAttribute(attribute: PDO::ATTR_SERVER_VERSION);
    }

    public function getName(string $driver): string
    {
        if (! $driver) {
            $driver = 'mysql';
        }
        $driver = strtolower(string: $driver);
        switch ($driver) {
            case strpos(haystack: $driver, needle: 'mssql'):
            case strpos(haystack: $driver, needle: 'sqlserver'):
            case strpos(haystack: $driver, needle: 'sqlsrv'):
                $driver = strpos(haystack: PHP_OS, needle: 'WIN') !== false ? 'sqlsrv' : 'dblib';
                break;
            case strpos(haystack: $driver, needle: 'sybase'):
                $driver = 'dblib';
                break;
            case strpos(haystack: $driver, needle: 'pgsql'):
                $driver = 'pgsql';
                break;
            case strpos(haystack: $driver, needle: 'sqlite'):
                $driver = 'sqlite';
                break;
            case strpos(haystack: $driver, needle: 'ibm'):
            case strpos(haystack: $driver, needle: 'db2'):
            case strpos(haystack: $driver, needle: 'odbc'):
                $driver = 'ibm';
                break;
            case strpos(haystack: $driver, needle: 'oracle'):
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
                throw new Exception(message: 'No Connection has been established with the database.');
            }

            foreach ($this->commands as $command) {
                $this->command(sql: $command['sql'], params: $command['params']);
            }
        } catch (Exception $a) {
            trigger_error(message: $a->getMessage(), error_level: E_USER_ERROR);
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
        if (is_null__($this->schemaCompiler)) {
            $this->schemaCompiler = match ($this->getDriver()) {
                'mysql' => new Schema\Compiler\MySQL(connection: $this),
                'pgsql' => new Schema\Compiler\PostgreSQL(connection: $this),
                'dblib', 'mssql', 'sqlsrv', 'sybase' => new Schema\Compiler\SQLServer(connection: $this),
                'sqlite' => new Schema\Compiler\SQLite(connection: $this),
                'oci', 'oracle' => new Schema\Compiler\Oracle(connection: $this),
                default => throw new Exception(message: 'Schema not supported yet.'),
            };

            $this->schemaCompiler->setOptions(options: $this->schemaCompilerOptions);
        }

        return $this->schemaCompiler;
    }

    /**
     * Close the current connection by destroying the associated PDO object
     */
    public function disconnect(): void
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
    public function initCommand(string $query, array $params = []): Connection
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
     * @return string|null
     */
    public function getDsn(): ?string
    {
        return $this->dsn;
    }

    /**
     * Returns the driver's name.
     *
     * @return string|null
     */
    public function getDriver(): ?string
    {
        if (is_null__($this->pdoDriver)) {
            $this->pdoDriver = $this->getPdo()->getAttribute(attribute: PDO::ATTR_DRIVER_NAME);
        }

        return $this->pdoDriver;
    }

    /**
     * Returns the schema associated with this connection
     */
    public function getSchema(): Schema
    {
        if (is_null__($this->schema)) {
            $this->schema = new Schema(connection: $this);
        }

        return $this->schema;
    }

    /**
     * Execute a query
     *
     * @param string $sql SQL Query.
     * @param array $params (optional) Query params.
     * @return ResultSet
     */
    public function query(string $sql, array $params = []): ResultSet
    {
        $prepared = $this->prepare(query: $sql, params: $params);
        $this->pdoExecute(prepared: $prepared);
        return new ResultSet(statement: $prepared['statement']);
    }

    /**
     * Execute a non-query SQL command.
     *
     * @param string $sql SQL Command.
     * @param array $params (optional) Command params.
     * @return bool
     */
    public function command(string $sql, array $params = []): bool
    {
        return $this->pdoExecute(prepared: $this->prepare(query: $sql, params: $params));
    }

    /**
     * Execute a query and return the number of affected rows.
     *
     * @param string $sql SQL Query.
     * @param array $params (optional) Query params.
     * @return  int
     */
    public function affectedRows(string $sql, array $params = []): int
    {
        $prepared = $this->prepare(query: $sql, params: $params);
        $this->pdoExecute(prepared: $prepared);
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
    public function column(string $sql, array $params = []): mixed
    {
        $prepared = $this->prepare(query: $sql, params: $params);
        $this->pdoExecute(prepared: $prepared);
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
        return preg_replace_callback(pattern: '/\?/', callback: function () use (&$params) {
            $param = array_shift($params);
            $param = is_object(value: $param) ? get_class(object: $param) : $param;

            if (is_int(value: $param) || is_float(value: $param)) {
                return $param;
            } elseif (is_null__(var: $param)) {
                return 'null';
            } elseif (is_bool(value: $param)) {
                return $param ? 'true' : 'false';
            } else {
                return $this->getPdo()->quote(string: $param);
            }
        }, subject: $query);
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
            $statement = $this->getPdo()->prepare(query: $query);
        } catch (PDOException $e) {
            throw new DbalException(
                message: $e->getMessage() . ' [ ' . $this->replaceParams(query: $query, params: $params) . ' ] ',
                code: (int) $e->getCode(),
                previous: $e->getPrevious()
            );
        }

        return ['query' => $query, 'params' => $params, 'statement' => $statement];
    }

    /**
     * @param PDOStatement $statement
     * @param array $values
     */
    protected function bindValues(PDOStatement $statement, array $values)
    {
        foreach ($values as $key => $value) {
            $param = PDO::PARAM_STR;

            if (is_null__(var: $value)) {
                $param = PDO::PARAM_NULL;
            } elseif (is_int(value: $value)) {
                $param = PDO::PARAM_INT;
            } elseif (is_bool(value: $value)) {
                $param = PDO::PARAM_BOOL;
            }

            $statement->bindValue(param: $key + 1, value: $value, type: $param);
        }
    }

    /**
     * Quotes an identifier
     *
     * @param mixed $value value to quote
     * @return  string  quoted identifier
     * @throws Exception
     */
    public function quoteIdentifier(mixed $value): mixed
    {
        if ($value === '*') {
            return $value;
        }

        if (is_object(value: $value)) {
            if ($value instanceof Base) {
                // Create a sub-query
                return '(' . $value->compile(connection: $this) . ')';
            } elseif ($value instanceof Expression) {
                // Use a raw expression
                return $value->handle($this->compiler);
            } elseif ($value instanceof Fnc) {
                return $this->compiler->compilePartFnc($value);
            } else {
                // Convert the object to a string
                return $this->quoteIdentifier(value: (string) $value);
            }
        }

        if (is_array(value: $value)) {
            // Separate the column and alias
            [$_value, $alias] = $value;
            return $this->quoteIdentifier(value: $_value) . ' AS ' . $this->quoteIdentifier(value: $alias);
        }

        if (strpos(haystack: $value, needle: '"') !== false) {
            // Quote the column in FUNC("ident") identifiers
            return preg_replace_callback(pattern: '/"(.+?)"/', callback: function ($matches) {
                return $this->quoteIdentifier($matches[1]);
            }, subject: $value);
        }

        if (strpos(haystack: $value, needle: '.') !== false) {
            // Split the identifier into the individual parts
            $parts = explode(separator: '.', string: $value);

            // Quote each of the parts
            return implode(separator: '.', array: array_map(callback: [$this, __FUNCTION__], array: $parts));
        }

        return static::$tableQuote . $value . static::$tableQuote;
    }

    /**
     * Quote a value for an SQL query.
     *
     * Objects passed to this function will be converted to strings.
     * Expression objects will use the value of the expression.
     * Query objects will be compiled and converted to a sub-query.
     * Fnc objects will be sent of for compiling.
     * All other objects will be converted using the `__toString` method.
     *
     * @param float|array|bool|int|string|Base|Expression|Fnc|null $value any value to quote
     * @return int|string
     */
    public function quote(Expression|float|Base|array|bool|int|string|Fnc $value = null): int|string
    {
        try {
            if (! $this->pdoInstance instanceof PDO) {
                throw new Exception(message: 'No PDOInstance has been made with the connection.');
            }

            if (is_null__(var: $value)) {
                return 'NULL';
            }

            if (is_bool(value: $value)) {
                return $value ? 1 : 0;
            }

            if (is_object(value: $value)) {
                if ($value instanceof Base) {
                    // create a sub-query
                    return '(' . $value->compile(connection: $this) . ')';
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
                    return $this->quote(value: (string) $value);
                }
            }

            if (is_array(value: $value)) {
                return '(' . implode(separator: ', ', array: array_map(callback: [$this, 'quote'], array: $value)) . ')';
            }

            if (is_int(value: $value)) {
                return (int) $value;
            }

            if (is_float(value: $value)) {
                // Convert to non-locale aware float to prevent possible commas
                return sprintf('%F', $value);
            }

            if (is_numeric(value: $value) && ! is_string(value: $value)) {
                return (string) $value;
            }
        } catch (Exception $a) {
            trigger_error(message: $a->getMessage(), error_level: E_USER_ERROR);
        }

        return $this->pdoInstance->quote(string: $value);
    }

    /**
     * Sets the connection encoding.
     *
     * @param string $charset Encoding.
     */
    protected function setCharset(string $charset): void
    {
        if (! empty($charset)) {
            $this->pdoInstance->exec(statement: "SET NAMES {$this->quote(value: $charset)}");
        }
    }

    /**
     * Get the query compiler.
     * @throws Exception
     */
    protected function getCompiler(): Compiler
    {
        if (! $this->compiler) {
            $class = 'Qubus\\Dbal\\Sql\\Compiler\\' . ucfirst(string: $this->driver);

            if (! class_exists(class: $class)) {
                throw new Exception(message: 'Cannot locate compiler for dialect: ' . $class);
            }

            $this->compiler = new $class($this);
        }

        return $this->compiler;
    }

    /**
     * Executes a prepared query and returns true on success or false on failure.
     *
     * @param   array $prepared Prepared query
     * @return  bool
     */
    protected function pdoExecute(array $prepared): bool
    {
        try {
            if ($prepared['params']) {
                $this->bindValues(statement: $prepared['statement'], values: $prepared['params']);
            }
            $result = $prepared['statement']->execute();
        } catch (PDOException $e) {
            throw new DbalException(message: $e->getMessage() . ' [ ' . $this->replaceParams(
                query: $prepared['query'],
                params: $prepared['params']
            ) . ' ] ', code: (int) $e->getCode(), previous: $e->getPrevious());
        }

        return $result;
    }

    /**
     * Executes a query on a connection
     *
     * @param mixed $query query object
     * @param string|null $type query type
     * @param array $bindings query bindings
     * @return  array|bool|int   query results
     * @throws Exception
     */
    public function execute(mixed $query, ?string $type = null, array $bindings = []): array|bool|int
    {
        if (! $query instanceof Base) {
            $query = new Query(query: $query, type: $type);
        }

        $type = $type ?: $query->getType();
        $sql = $this->compile(query: $query, type: $type, bindings: $bindings);

        $profilerData = [
            'query'  => $sql,
            'start'  => microtime(as_float: true),
            'type'   => $type,
            'driver' => static::class . ':' . $this->driver,
        ];

        // fire start callback for profiling
        $this->profilerCallbacks['start'] instanceof Closure && $this->profilerCallbacks['start']($profilerData);

        try {
            $result = $this->pdoInstance->prepare(query: $sql);
            $result->execute(params: $bindings);
        } catch (PDOException $e) {
            $code = is_int(value: $e->getCode()) ? $e->getCode() : 0;
            throw new DbalException(message: $e->getMessage() . ' from QUERY: ' . $sql, code: $code);
        }

        if ($type === DB::SELECT) {
            $asObject = $query->getAsObject();
            $asObject === null && $asObject = $this->config['asObject'];

            if (! $asObject) {
                $result = $result->fetchAll(PDO::FETCH_ASSOC);
            } elseif (is_string(value: $asObject)) {
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
                $this->pdoInstance->lastInsertId(name: $query->insertIdField() ?: $this->insertIdField),
                $result->rowCount(),
            ];
        } else {
            $result = $result->errorCode() === '00000' ? $result->rowCount() : -1;
        }

        $profilerData['end'] = microtime(as_float: true);
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
     * @param mixed $query query object
     * @param string|null $type query type
     * @param array $bindings query bindings
     * @return string
     * @throws Exception
     */
    public function compile(mixed $query, ?string $type = null, array $bindings = []): string
    {
        if (! $query instanceof Base) {
            $query = new Query(query: $query, type: $type);
        }

        // Re-retrieve the query type
        $type = $query->getType();

        return $this->getCompiler()->compile(query: $query, type: $type, bindings: $bindings);
    }

    /**
     * Sets transaction savepoint.
     *
     * @param mixed $savepoint Savepoint name.
     */
    public function setSavepoint(mixed $savepoint = null): static
    {
        $savepoint || $savepoint = 'DBAL_SP_LEVEL_' . ++$this->savepoint;
        $this->pdoInstance->query(statement: 'SAVEPOINT ' . $savepoint);

        return $this;
    }

    /**
     * Roll back to a transaction savepoint.
     *
     * @param mixed $savepoint Savepoint name.
     * @return DbalPdo
     */
    public function rollbackSavepoint(mixed $savepoint = null): static
    {
        if (! $savepoint) {
            $savepoint = 'DBAL_SP_LEVEL_' . $this->savepoint;
            $this->savepoint--;
        }

        $this->pdoInstance->query(statement: 'ROLLBACK TO SAVEPOINT ' . $savepoint);

        return $this;
    }

    /**
     * Release a transaction savepoint.
     *
     * @param mixed $savepoint Savepoint name.
     */
    public function releaseSavepoint(mixed $savepoint = null): static
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
