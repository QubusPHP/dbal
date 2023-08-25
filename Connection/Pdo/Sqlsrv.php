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

namespace Qubus\Dbal\Connection\Pdo;

use PDO;
use Qubus\Dbal\Connection\DbalPdo;

class Sqlsrv extends DbalPdo
{
    /**
     * Sets the connection encoding.
     *
     * @param string $charset  encoding
     */
    protected function setCharset(string $charset): void
    {
        if ($charset === 'utf8' || $charset = 'utf-8') {
            // use utf8 encoding
            $this->pdoInstance->setAttribute(attribute: PDO::SQLSRV_ATTR_ENCODING, value: PDO::SQLSRV_ENCODING_UTF8);
        } elseif ($charset === 'system') {
            // use system encoding
            $this->pdoInstance->setAttribute(attribute: PDO::SQLSRV_ATTR_ENCODING, value: PDO::SQLSRV_ENCODING_SYSTEM);
        } elseif (is_numeric(value: $charset)) {
            // charset code passed directly
            $this->pdoInstance->setAttribute(attribute: PDO::SQLSRV_ATTR_ENCODING, value: $charset);
        } else {
            // unknown charset, use the default encoding
            $this->pdoInstance->setAttribute(attribute: PDO::SQLSRV_ATTR_ENCODING, value: PDO::SQLSRV_ENCODING_DEFAULT);
        }
    }
}
