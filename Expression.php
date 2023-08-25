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

class Expression
{
    /** @var  mixed  $value  the raw expression */
    protected mixed $value;

    /**
     * @param mixed $value expression value
     */
    public function __construct(mixed $value)
    {
        $this->value = $value;
    }

    /**
     * Get the expression value as a string.
     *
     *     $sql = $expression->value();
     *
     * @return  string
     */
    public function value(): string
    {
        return (string) $this->value;
    }

    /**
     * Returns the expression value.
     *
     * @param mixed ...$arg Compiler.
     * @return  mixed  The expression value
     */
    public function handle(mixed $arg): mixed
    {
        return $this->value;
    }

    /**
     * Return the value of the expression as a string.
     *
     *     echo $expression;
     *
     * @return  string
     * @uses    Expression::value
     */
    public function __toString()
    {
        return $this->value();
    }
}
