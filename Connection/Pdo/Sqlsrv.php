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
        if ($charset === 'utf8' || $charset = 'utf-8') {
            // use utf8 encoding
            $this->connection->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_UTF8);
        } elseif ($charset === 'system') {
            // use system encoding
            $this->connection->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_SYSTEM);
        } elseif (is_numeric($charset)) {
            // charset code passed directly
            $this->connection->setAttribute(PDO::SQLSRV_ATTR_ENCODING, $charset);
        } else {
            // unknown charset, use the default encoding
            $this->connection->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_DEFAULT);
        }
    }
}
