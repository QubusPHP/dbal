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

class Value extends Expression
{
    /**
     * Handles value quoting.
     *
     * @return Compiler $compiler Quoted identifier.
     */
    public function handle($compiler): mixed
    {
        return $compiler->quote($this->value);
    }
}
