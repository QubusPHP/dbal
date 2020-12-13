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

use Qubus\Dbal\Sql\Compiler;

class Identifier extends Expression
{
    /**
     * Handles identifier quoting.
     *
     * @return  string quoted identifier
     */
    public function handle(Compiler $compiler)
    {
        return $compiler->quoteIdentifier($this->value);
    }
}
