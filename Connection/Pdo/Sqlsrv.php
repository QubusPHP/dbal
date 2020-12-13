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

namespace Qubus\Dbal\Connection\Pdo;

use PDO;
use Qubus\Dbal\Connection\DbalPdo;

use function constant;
use function strtoupper;

class Sqlsrv extends DbalPdo
{
    /**
     * Sets the connection encoding.
     *
     * @param  string  $charset  encoding
     */
    protected function setCharset($charset)
    {
        $this->connection->setAttribute(PDO::SQLSRV_ATTR_ENCODING, constant("\PDO::SQLSRV_ENCODING_" . strtoupper($charset)));
    }
}
