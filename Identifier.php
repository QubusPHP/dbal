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

use Qubus\Dbal\Sql\Compiler;

class Identifier extends Expression
{
    /**
     * Handles identifier quoting.
     *
     * @return Compiler $compiler quoted identifier
     */
    public function handle($compiler): mixed
    {
        return $compiler->quoteIdentifier($this->value);
    }
}
