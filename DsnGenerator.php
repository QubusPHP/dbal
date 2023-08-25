<?php

/**
 * Qubus\Dbal
 *
 * @link       https://github.com/QubusPHP/dbal
 * @copyright  2020
 * @author     Joshua Parker <joshua@joshuaparker.dev>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types=1);

namespace Qubus\Dbal;

class DsnGenerator
{
    /**
     * Generates Sql Server DSN.
     *
     * @param Connection $conn
     * @return string
     */
    public function getSqlsrvDNS(Connection $conn): string
    {
        $dsn = 'sqlsrv:server=';
        if (isset($conn->getConfigurations()['host'])) {
            $dsn .= $conn->getConfigurations()['host'];
        }
        if (isset($conn->getConfigurations()['port']) && ! empty($conn->getConfigurations()['port'])) {
            $dsn .= ',' . $conn->getConfigurations()['port'];
        }
        if (isset($conn->getConfigurations()['dbname'])) {
            $dsn .= ';Database=' . $conn->getConfigurations()['dbname'];
        }
        if (isset($conn->getConfigurations()['MultipleActiveResultSets'])) {
            $dsn .= '; MultipleActiveResultSets=' . ($conn->getConfigurations()['MultipleActiveResultSets'] ? 'true' : 'false');
        }
        return $dsn;
    }

    /**
     * Generates Dblib DSN.
     *
     * @param Connection $conn
     * @return string
     */
    public function getDblibDNS(Connection $conn): string
    {
        $dsn = 'dblib:host=';
        if (isset($conn->getConfigurations()['host'])) {
            $dsn .= $conn->getConfigurations()['host'];
        }
        if (isset($conn->getConfigurations()['port']) && ! empty($conn->getConfigurations()['port'])) {
            $dsn .= ':' . $conn->getConfigurations()['port'];
        }
        if (isset($conn->getConfigurations()['dbname'])) {
            $dsn .= ';dbname=' . $conn->getConfigurations()['dbname'];
        }
        return $dsn;
    }

    /**
     * Generates Sqlite DSN.
     *
     * @param Connection $conn
     * @return string
     */
    public function getSqliteDNS(Connection $conn): string
    {
        $dsn = 'sqlite:';
        if (isset($conn->getConfigurations()['path'])) {
            $dsn .= $conn->getConfigurations()['path'];
        } elseif (isset($conn->getConfigurations()['memory'])) {
            $dsn .= ':memory:';
        }
        return $dsn;
    }

    /**
     * Generates Postgres DSN.
     *
     * @param Connection $conn
     * @return string
     */
    public function getPgsqlDNS(Connection $conn): string
    {
        $dsn = 'pgsql:';
        if (isset($conn->getConfigurations()['host']) && ! empty($conn->getConfigurations()['host'])) {
            $dsn .= 'host=' . $conn->getConfigurations()['host'] . ' ';
        }
        if (isset($conn->getConfigurations()['port']) && ! empty($conn->getConfigurations()['port'])) {
            $dsn .= 'port=' . $conn->getConfigurations()['port'] . ' ';
        }
        if (isset($conn->getConfigurations()['dbname'])) {
            $dsn .= 'dbname=' . $conn->getConfigurations()['dbname'] . ' ';
        } else {
            // Used for temporary connections to allow operations like dropping the database currently connected to.
            // Connecting without an explicit database does not work, therefore "template1" database is used
            // as it is certainly present in every server setup.
            $dsn .= 'dbname=template1' . ' ';
        }
        if (isset($conn->getConfigurations()['sslmode'])) {
            $dsn .= 'sslmode=' . $conn->getConfigurations()['sslmode'] . ' ';
        }
        return $dsn;
    }

    /**
     * Generates Oracle DSN.
     *
     * @param Connection $conn
     * @return string
     */
    public function getOracleDNS(Connection $conn): string
    {
        $dsn = 'pgsql:';
        if (isset($conn->getConfigurations()['dbname'])) {
            $dsn .= 'dbname=//' . $conn->getConfigurations()['dbname'];
        }
    }

    /**
     * Generates IBM DSN.
     *
     * @param Connection $conn
     * @return string
     */
    public function getIbmDNS(Connection $conn): string
    {
        $dsn = 'ibm:DRIVER={IBM DB2 ODBC DRIVER};';
        if (isset($conn->getConfigurations()['host'])) {
            $dsn .= 'HOSTNAME=' . $conn->getConfigurations()['host'] . ';';
        }
        if (isset($conn->getConfigurations()['port'])) {
            $dsn .= 'PORT=' . $conn->getConfigurations()['port'] . ';';
        }
        $dsn .= 'PROTOCOL=TCPIP;';
        if (isset($conn->getConfigurations()['dbname'])) {
            $dsn .= 'DATABASE=' . $conn->getConfigurations()['dbname'] . ';';
        }
        return $dsn;
    }

    /**
     * Generates Mysql DSN.
     *
     * @param Connection $conn
     * @return string
     */
    public function getMysqlDNS(Connection $conn): string
    {
        $dsn = 'mysql:';
        if (isset($conn->getConfigurations()['host']) && ! empty($conn->getConfigurations()['host'])) {
            $dsn .= 'host=' . $conn->getConfigurations()['host'] . ';';
        }
        if (isset($conn->getConfigurations()['port'])) {
            $dsn .= 'port=' . $conn->getConfigurations()['port'] . ';';
        }
        if (isset($conn->getConfigurations()['dbname'])) {
            $dsn .= 'dbname=' . $conn->getConfigurations()['dbname'] . ';';
        }
        if (isset($conn->getConfigurations()['unix_socket'])) {
            $dsn .= 'unix_socket=' . $conn->getConfigurations()['unix_socket'] . ';';
        }
        if (isset($conn->getConfigurations()['charset'])) {
            $dsn .= 'charset=' . $conn->getConfigurations()['charset'] . ';';
        }
        return $dsn;
    }
}
