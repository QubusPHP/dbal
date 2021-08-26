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

class Expression
{
    /** @var  mixed  $value  the raw expression */
    protected $value;

    /**
     * @param  mixed  expression value
     */
    public function __construct($value)
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
    public function value()
    {
        return (string) $this->value;
    }

    /**
     * Returns the expression value.
     *
     * @param   object Compiler.
     * @return  mixed  The expression value
     */
    public function handle(...$arg)
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
